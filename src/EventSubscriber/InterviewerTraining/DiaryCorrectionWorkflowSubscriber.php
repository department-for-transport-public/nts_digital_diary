<?php

namespace App\EventSubscriber\InterviewerTraining;

use App\Entity\DiaryKeeper;
use App\Entity\InterviewerTrainingRecord;
use App\Entity\User;
use App\Event\SubmitHouseholdEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\SwitchUserEvent;
use Symfony\Component\Security\Http\SecurityEvents;
use Symfony\Component\Workflow\Event\EnteredEvent;
use Symfony\Component\Workflow\Registry;

class DiaryCorrectionWorkflowSubscriber implements EventSubscriberInterface
{
    public function __construct(
        protected readonly Registry               $workflowRegistry,
        protected readonly EntityManagerInterface $entityManager,
        protected readonly Security               $security,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            SecurityEvents::SWITCH_USER => 'switchUser',
            SubmitHouseholdEvent::class => 'submitHousehold',
            'workflow.travel_diary_state.entered.' . DiaryKeeper::STATE_APPROVED => 'diaryKeeperApproved',
        ];
    }

    public function submitHousehold(SubmitHouseholdEvent $event): void
    {
        $areaPeriod = $event->getHousehold()->getAreaPeriod();
        if ($areaPeriod->getTrainingInterviewer()) {
            $this->attemptTransition($areaPeriod->getLatestTrainingRecord(), InterviewerTrainingRecord::TRANSITION_COMPLETE);
        }
    }

    /**
     * If the interviewer tries to approve the diaries without having edited them
     * we still want it to be marked as in progress when they approve a diary
     */
    public function diaryKeeperApproved(EnteredEvent $event): void
    {
        /** @var DiaryKeeper $diaryKeeper */
        $diaryKeeper = $event->getSubject();
        $areaPeriod = $diaryKeeper->getHousehold()->getAreaPeriod();
        if ($areaPeriod->getTrainingInterviewer()) {
            $this->attemptTransition($areaPeriod->getLatestTrainingRecord(), InterviewerTrainingRecord::TRANSITION_START);
        }
    }

    public function switchUser(SwitchUserEvent $event): void
    {
        /** @var User $user */
        $user = $event->getTargetUser();
        $areaPeriod = $user->getDiaryKeeper()?->getHousehold()?->getAreaPeriod();

        if (
            !in_array('ROLE_DIARY_KEEPER', $event->getToken()->getRoleNames())
            || !$this->security->isGranted('ROLE_INTERVIEWER')
            || !$areaPeriod?->getTrainingInterviewer()
        ) {
            return;
        }

        $this->attemptTransition($areaPeriod->getLatestTrainingRecord(), InterviewerTrainingRecord::TRANSITION_START);
    }

    protected function attemptTransition(InterviewerTrainingRecord $trainingRecord, string $transition)
    {
        if ($trainingRecord->getModuleName() !== InterviewerTrainingRecord::MODULE_DIARY_CORRECTION) {
            return;
        }

        $workflow = $this->workflowRegistry->get($trainingRecord);
        if ($workflow->can($trainingRecord, $transition)) {
            $workflow->apply($trainingRecord, $transition);
            $this->entityManager->flush();
        }
    }
}