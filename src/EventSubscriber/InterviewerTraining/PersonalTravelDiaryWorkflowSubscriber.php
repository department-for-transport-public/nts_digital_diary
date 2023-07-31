<?php

namespace App\EventSubscriber\InterviewerTraining;

use App\Entity\DiaryKeeper;
use App\Entity\InterviewerTrainingRecord;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\EnteredEvent;
use Symfony\Component\Workflow\Registry;

class PersonalTravelDiaryWorkflowSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly Registry $workflowRegistry) {}

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.travel_diary_state.entered.' . DiaryKeeper::STATE_IN_PROGRESS => 'startModule',
            'workflow.travel_diary_state.entered.' . DiaryKeeper::STATE_COMPLETED   => 'completeModule',
        ];
    }

    public function completeModule(EnteredEvent $event)
    {
        $this->attemptTransition($event,InterviewerTrainingRecord::TRANSITION_COMPLETE);
    }

    public function startModule(EnteredEvent $event)
    {
        $this->attemptTransition($event, InterviewerTrainingRecord::TRANSITION_START);
    }

    protected function attemptTransition(EnteredEvent $event, string $transition)
    {
        /** @var DiaryKeeper $diaryKeeper */
        $diaryKeeper = $event->getSubject();
        $areaPeriod = $diaryKeeper->getHousehold()->getAreaPeriod();
        if (!$areaPeriod->getTrainingInterviewer()
            || $areaPeriod->getLatestTrainingRecord()->getModuleName() !== InterviewerTrainingRecord::MODULE_PERSONAL_TRAVEL_DIARY
        ) {
            return;
        }

        $trainingRecord = $areaPeriod->getLatestTrainingRecord();
        $workflow = $this->workflowRegistry->get($trainingRecord);
        if ($workflow->can($trainingRecord, $transition)) {
            $workflow->apply($trainingRecord, $transition);
        }
    }
}