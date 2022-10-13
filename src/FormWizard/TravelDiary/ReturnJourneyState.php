<?php

namespace App\FormWizard\TravelDiary;

use Symfony\Component\Translation\TranslatableMessage;

class ReturnJourneyState extends AbstractDuplicateJourneyState
{
    const STATE_INTRODUCTION = 'introduction';
    const STATE_DAY = 'day';
    const STATE_PURPOSE = 'purpose';
    const STATE_TIMES = 'times';
    const STATE_STAGE_DETAILS = 'stage-details';
    const STATE_FINISH = 'finish';

    const TRANSITION_INTRO_TO_DAY = 'introduction-to-day';
    const TRANSITION_DAY_TO_PURPOSE = 'day-to-purpose';
    const TRANSITION_DAY_TO_TIMES = 'day-to-times';
    const TRANSITION_PURPOSE_TO_TIMES = 'purpose-to-times';
    const TRANSITION_TIMES_TO_FINISH = 'times-to-finish';
    const TRANSITION_TIMES_TO_STAGE = 'times-to-stage';
    const TRANSITION_STAGE_TO_NEXT_STAGE = 'stage-to-next-stage';
    const TRANSITION_STAGE_TO_FINISH = 'stage-to-finish';

    public ?string $sourceJourneyId = null;

    public function getStageDetailsTitle(): TranslatableMessage
    {
        return new TranslatableMessage('return-journey.stage-details.page-title', [
            'number' => $this->getStageNumber(),
            // as this is part of place metadata, this was throwing an exception when clicking back after finishing, so we need to check
            'method' => $this->getContextStage() ? $this->getContextStage()->getMethodForDisplay() : null,
        ], 'travel-diary');
    }
}