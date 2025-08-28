<?php

namespace App\FormWizard\TravelDiary;

use Symfony\Component\Translation\TranslatableMessage;

class ReturnJourneyState extends AbstractDuplicateJourneyState
{
    public const STATE_INTRODUCTION = 'introduction';
    public const STATE_DAY = 'day';
    public const STATE_PURPOSE = 'purpose';
    public const STATE_TIMES = 'times';
    public const STATE_STAGE_DETAILS = 'stage-details';
    public const STATE_FINISH = 'finish';

    public const TRANSITION_INTRO_TO_DAY = 'introduction-to-day';
    public const TRANSITION_DAY_TO_PURPOSE = 'day-to-purpose';
    public const TRANSITION_DAY_TO_TIMES = 'day-to-times';
    public const TRANSITION_PURPOSE_TO_TIMES = 'purpose-to-times';
    public const TRANSITION_TIMES_TO_FINISH = 'times-to-finish';
    public const TRANSITION_TIMES_TO_STAGE = 'times-to-stage';
    public const TRANSITION_STAGE_TO_NEXT_STAGE = 'stage-to-next-stage';
    public const TRANSITION_STAGE_TO_FINISH = 'stage-to-finish';

    public ?string $sourceJourneyId = null;

    public function getStageDetailsTitle(): TranslatableMessage
    {
        return new TranslatableMessage('return-journey.stage-details.page-title', [
            'number' => $this->getStageNumber(),
            // as this is part of place metadata, this was throwing an exception when clicking back after finishing, so we need to check
            'method' => $this->getContextStage() ? $this->getContextStage()->getMethodForDisplay(true) : null,
        ], 'travel-diary');
    }
}