<?php

namespace App\FormWizard\TravelDiary;

use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints as Assert;

class RepeatJourneyState extends AbstractDuplicateJourneyState
{
    public const STATE_FULL_INTRODUCTION = 'full-introduction';
    public const STATE_SELECT_SOURCE_DAY = 'select-source-day';
    public const STATE_SELECT_SOURCE_JOURNEY = 'select-source-journey';
    public const STATE_PURPOSE = 'purpose';
    public const STATE_ADJUST_TIMES = 'adjust-times';
    public const STATE_ADJUST_STAGE_DETAILS = 'adjust-stage-details';
    public const STATE_FINISH = 'finish';

    public const TRANSITION_FULL_INTRO_TO_SOURCE_DAY = 'introduction-to-source-day';
    public const TRANSITION_DAY_TO_SOURCE_JOURNEY = 'source-day-to-source-journey';
    public const TRANSITION_SOURCE_JOURNEY_TO_PURPOSE = 'source-journey-to-purpose';
    public const TRANSITION_PURPOSE_TO_TIMES = 'purpose-to-times';
    public const TRANSITION_TIMES_TO_FINISH = 'times-to-finish';
    public const TRANSITION_TIMES_TO_STAGE = 'times-to-stage';
    public const TRANSITION_STAGE_TO_NEXT_STAGE = 'stage-to-next-stage';
    public const TRANSITION_STAGE_TO_FINISH = 'stage-to-finish';

    #[Assert\NotNull(message: 'wizard.repeat-journey.source-day.not-null', groups: ['wizard.repeat-journey.source-day'])]
    public ?string $sourceDayId = null;

    #[Assert\NotNull(message: 'wizard.repeat-journey.source-journey.not-null', groups: ['wizard.repeat-journey.source-journey'])]
    public ?string $sourceJourneyId = null;

    public int $targetDayNumber;

    public function getStageDetailsTitle(): TranslatableMessage
    {
        return new TranslatableMessage('repeat-journey.stage-details.page-title', [
            'number' => $this->getStageNumber(),
            // as this is part of place metadata, this was throwing an exception when clicking back after finishing, so we need to check
            'method' => $this->getContextStage() ? $this->getContextStage()->getMethodForDisplay(true) : null,
        ], 'travel-diary');
    }

    public function isPracticeDay(): bool
    {
        return $this->targetDayNumber === 0;
    }

    public function setTargetDayNumber(int $targetDayNumber): self
    {
        $this->targetDayNumber = $targetDayNumber;
        return $this;
    }
}