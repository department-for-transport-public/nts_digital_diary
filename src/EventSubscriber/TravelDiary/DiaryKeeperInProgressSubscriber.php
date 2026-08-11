<?php

namespace App\EventSubscriber\TravelDiary;

use App\Entity\DiaryKeeper;
use App\Entity\Journey\Journey;
use App\Entity\Journey\Stage;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Events;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * We need to transition the DiaryKeeper when a journey is created by or for them.
 * However, we need to recompute the change set in onFlush (because Doctrine).
 * We make the change in prePersist, because other event listeners rely on the DK
 * transition, and need to be done during preFlush
 */
#[AsDoctrineListener(event: Events::onFlush)]
#[AsDoctrineListener(event: Events::prePersist)]
class DiaryKeeperInProgressSubscriber
{
    protected array $changedDiaryKeepers = [];

    public function __construct(
        protected WorkflowInterface             $travelDiaryStateStateMachine,
    ) {}

    public function prePersist(PrePersistEventArgs $eventArgs): void
    {
        $entity = $eventArgs->getObject();

        // Set diary to "in-progress" (if not already in that state) when creating a Journey
        if ($entity instanceof Journey) {
            $diaryDay = $entity->getDiaryDay();
            if (!$diaryDay || $diaryDay->isPracticeDay()) {
                // Practice day - don't change state
                return;
            }

            $diaryKeeper = $entity->getDiaryDay()->getDiaryKeeper();
            if ($this->travelDiaryStateStateMachine->can($diaryKeeper, DiaryKeeper::TRANSITION_START)) {
                $this->travelDiaryStateStateMachine->apply($diaryKeeper, DiaryKeeper::TRANSITION_START);
                $this->changedDiaryKeepers[] = $diaryKeeper;
            }
        }
    }

    public function onFlush(OnFlushEventArgs $eventArgs): void
    {
        $objectManager = $eventArgs->getObjectManager();
        if (!$objectManager->getMetadataFactory()->hasMetadataFor(DiaryKeeper::class)) {
            return;
        }

        /** @var Journey $journey */
        foreach ($this->changedDiaryKeepers as $diaryKeeper) {
            // If this is a shared journey, the diaryKeeper was a proxy before computing the UOW, so recompute it
            $objectManager->getUnitOfWork()->recomputeSingleEntityChangeSet(
                $objectManager->getClassMetadata(DiaryKeeper::class),
                $diaryKeeper
            );
        }
        $this->changedDiaryKeepers = [];
    }
}
