<?php

namespace App\FormWizard\TravelDiary;

use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints as Assert;

class RepeatJourneyState extends AbstractDuplicateJourneyState
{
    const STATE_FULL_INTRODUCTION = 'full-introduction';
    const STATE_SELECT_SOURCE_DAY = 'select-source-day';
    const STATE_SELECT_SOURCE_JOURNEY = 'select-source-journey';
    const STATE_ALT_INTRODUCTION = 'alt-introduction';
    const STATE_SELECT_TARGET_DAY = 'select-target-day';
    const STATE_PURPOSE = 'purpose';
    const STATE_ADJUST_TIMES = 'adjust-times';
    const STATE_ADJUST_STAGE_DETAILS = 'adjust-stage-details';
    const STATE_FINISH = 'finish';

    const TRANSITION_FULL_INTRO_TO_SOURCE_DAY = 'introduction-to-source-day';
    const TRANSITION_DAY_TO_SOURCE_JOURNEY = 'source-day-to-source-journey';
    const TRANSITION_SOURCE_JOURNEY_TO_TARGET_DAY = 'source-journey-to-target-day';
    const TRANSITION_ALT_INTRO_TO_TARGET_DAY = 'introduction-to-target-day';
    const TRANSITION_TARGET_DAY_TO_PURPOSE = 'target-day-to-purpose';
    const TRANSITION_PURPOSE_TO_TIMES = 'purpose-to-times';
    const TRANSITION_TIMES_TO_FINISH = 'times-to-finish';
    const TRANSITION_TIMES_TO_STAGE = 'times-to-stage';
    const TRANSITION_STAGE_TO_NEXT_STAGE = 'stage-to-next-stage';
    const TRANSITION_STAGE_TO_FINISH = 'stage-to-finish';

    /**
     * @Assert\NotNull(message="wizard.repeat-journey.source-day.not-null", groups={"wizard.repeat-journey.source-day"})
     */
    public ?string $sourceDayId = null;

    /**
     * @Assert\NotNull(message="wizard.repeat-journey.source-journey.not-null", groups={"wizard.repeat-journey.source-journey"})
     */
    public ?string $sourceJourneyId = null;

    public bool $isPracticeDay = false;

    public function getStageDetailsTitle(): TranslatableMessage
    {
        return new TranslatableMessage('repeat-journey.stage-details.page-title', [
            'number' => $this->getStageNumber(),
            // as this is part of place metadata, this was throwing an exception when clicking back after finishing, so we need to check
            'method' => $this->getContextStage() ? $this->getContextStage()->getMethodForDisplay() : null,
        ], 'travel-diary');
    }

    public function setIsPracticeDay(bool $isPracticeDay): self
    {
        $this->isPracticeDay = $isPracticeDay;
        return $this;
    }
}