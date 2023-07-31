<?php

namespace App\EventSubscriber\InterviewerTraining;

use App\Entity\InterviewerTrainingRecord;
use App\Entity\User;
use App\Event\SubmitHouseholdEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Event\SwitchUserEvent;
use Symfony\Component\Security\Http\SecurityEvents;
use Symfony\Component\Workflow\Registry;

class DiaryCorrectionWorkflowSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly Registry $workflowRegistry,
        private readonly EntityManagerInterface $entityManager,
        private readonly Security $security,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            SecurityEvents::SWITCH_USER => 'switchUser',
            SubmitHouseholdEvent::class => 'submitHousehold',
        ];
    }

    public function submitHousehold(SubmitHouseholdEvent $event)
    {
        $areaPeriod = $event->getHousehold()->getAreaPeriod();
        if ($areaPeriod->getTrainingInterviewer()) {
            $this->attemptTransition($areaPeriod->getLatestTrainingRecord(), InterviewerTrainingRecord::TRANSITION_COMPLETE);
        }
    }

    public function switchUser(SwitchUserEvent $event)
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