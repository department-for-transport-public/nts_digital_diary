<?php

namespace App\EventSubscriber;

use App\Entity\DiaryKeeper;
use App\Entity\Journey\Journey;
use App\Entity\Journey\Stage;
use App\Entity\User;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Workflow\WorkflowInterface;

class DiaryKeeperStateSubscriber implements EventSubscriber
{
    protected WorkflowInterface $travelDiaryStateStateMachine;
    protected AuthorizationCheckerInterface $authorizationChecker;
    protected Security $security;

    public function __construct(WorkflowInterface $travelDiaryStateStateMachine, AuthorizationCheckerInterface $authorizationChecker, Security $security)
    {
        $this->travelDiaryStateStateMachine = $travelDiaryStateStateMachine;
        $this->authorizationChecker = $authorizationChecker;
        $this->security = $security;
    }

    public function prePersist(LifecycleEventArgs $eventArgs): void
    {
        $entity = $eventArgs->getEntity();

        // Set diary to "started" (if not already in that state) when creating a Journey
        if ($entity instanceof Journey) {
            $diaryDay = $entity->getDiaryDay();
            if ($diaryDay->getNumber() === 0) {
                // Practice day - don't change state
                return;
            }
            $diaryKeeper = $diaryDay ? $diaryDay->getDiaryKeeper() : null;

            if ($this->travelDiaryStateStateMachine->can($diaryKeeper, DiaryKeeper::TRANSITION_START)) {
                $this->travelDiaryStateStateMachine->apply($diaryKeeper, DiaryKeeper::TRANSITION_START);
            }
        }
    }

    public function onFlush(OnFlushEventArgs $eventArgs): void
    {
        // If diary is edited, then run the "undo complete" transition, if available
        // (Effect: If the diary is "completed", make it "in progress" upon editing)
        $isImpersonator = $this->authorizationChecker->isGranted('IS_IMPERSONATOR');

        $entityManager = $eventArgs->getEntityManager();
        $unitOfWork = $entityManager->getUnitOfWork();

        $diaryKeeper = $this->getDiaryKeeper();
        if ($diaryKeeper !== null) {
            $hasQualifyingEdit = $this->hasQualifyingEdit($unitOfWork->getScheduledEntityInsertions())
                || $this->hasQualifyingEdit($unitOfWork->getScheduledEntityUpdates())
                || $this->hasQualifyingEdit($unitOfWork->getScheduledEntityDeletions());

            if ($hasQualifyingEdit) {
                $stateChanged = false;

                if (!$isImpersonator &&
                    $this->travelDiaryStateStateMachine->can($diaryKeeper, DiaryKeeper::TRANSITION_UNDO_COMPLETE)
                ) {
                    // Don't automatically reopen completed diaries if it's the interviewer doing the editing...
                    $this->travelDiaryStateStateMachine->apply($diaryKeeper, DiaryKeeper::TRANSITION_UNDO_COMPLETE);
                    $stateChanged = true;
                }

                if ($this->travelDiaryStateStateMachine->can($diaryKeeper, DiaryKeeper::TRANSITION_START)) {
                    // Just in case we have a survey stuck at "new" (and if we do, make this state change regardless of who
                    // is doing the editing!)
                    $this->travelDiaryStateStateMachine->apply($diaryKeeper, DiaryKeeper::TRANSITION_START);
                    $stateChanged = true;
                }

                if ($stateChanged) {
                    $diaryKeeperClassMetadata = $entityManager->getClassMetadata(DiaryKeeper::class);
                    $entityManager->getUnitOfWork()->recomputeSingleEntityChangeSet($diaryKeeperClassMetadata, $diaryKeeper);
                }
            }
        }
    }

    function hasQualifyingEdit(array $entities): bool
    {
        foreach($entities as $entity) {
            if ($this->isQualifyingEdit($entity)) {
                return true;
            }
        }
        return false;
    }

    protected function isQualifyingEdit(object $entity): bool
    {
        // Qualifying edits are to journeys or stages not on day 0 (practice day)
        return
            (get_class($entity) === Journey::class && $entity->getDiaryDay()->getNumber() !== 0)
            || (get_class($entity) === Stage::class && $entity->getJourney()->getDiaryDay()->getNumber() !== 0);
    }

    protected function getDiaryKeeper(): ?DiaryKeeper
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return null;
        }
        return $user->getDiaryKeeper();
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::prePersist,
            Events::onFlush,
        ];
    }
}