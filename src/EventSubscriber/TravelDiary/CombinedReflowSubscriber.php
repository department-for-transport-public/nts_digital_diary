<?php

namespace App\EventSubscriber\TravelDiary;

use App\Entity\Journey\Journey;
use App\FormWizard\TravelDiary\CombinedReflowState;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\EnterEvent;
use Symfony\Component\Workflow\Event\LeaveEvent;
use Symfony\Component\Workflow\Event\TransitionEvent;

class CombinedReflowSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        $workflowName = 'workflow.form_wizard.travel_diary.combined_reflow';
        return [
            "{$workflowName}.leave.wizard-type" => 'leavingWizardType',
            "{$workflowName}.enter.standard--stage-method" => 'enteringStageMethod',
            "{$workflowName}.transition.repeat__journey_details__fresh_stage_method" => ['duplicatingJourneyWithDifferentStagesTransitionEvent', 64],
            "{$workflowName}.transition.return__journey_details__fresh_stage_method" => ['duplicatingJourneyWithDifferentStagesTransitionEvent', 64],
        ];
    }

    public function leavingWizardType(LeaveEvent $event): void
    {
        /** @var CombinedReflowState $state */
        $state = $event->getSubject();
        $state->sourceDayNumber = null;
        $state->sourceJourneyId = null;
    }

    public function enteringStageMethod(EnterEvent $event): void
    {
        /** @var CombinedReflowState $state */
        $state = $event->getSubject();
        $state->clearHistoryForNewStage();
    }

    public function duplicatingJourneyWithDifferentStagesTransitionEvent(TransitionEvent $event): void
    {
        /** @var CombinedReflowState $state */
        $state = $event->getSubject();
        $state->clearStages();
    }
}