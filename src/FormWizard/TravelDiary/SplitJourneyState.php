<?php

namespace App\FormWizard\TravelDiary;

use App\FormWizard\AbstractFormWizardState;
use Symfony\Component\Validator\Constraints as Assert;

class SplitJourneyState extends AbstractFormWizardState
{
    const STATE_INTRODUCTION = 'introduction';
    const STATE_MIDPOINT = 'midpoint';
    const STATE_PURPOSE = 'purpose';
    const STATE_FINISH = 'finish';

    const TRANSITION_INTRO_TO_MIDPOINT = 'introduction-to-midpoint';
    const TRANSITION_MIDPOINT_TO_PURPOSE = 'midpoint-to-purpose';
    const TRANSITION_MIDPOINT_TO_FINISH = 'midpoint-to-finish';
    const TRANSITION_PURPOSE_TO_FINISH = 'purpose-to-finish';

    /**
     * @Assert\NotNull(message="wizard.split-journey.source-journey.not-null", groups={"wizard.split-journey.source-journey"})
     */
    public ?string $sourceJourneyId = null;
    protected ?SplitJourneySubject $subject = null;

    public function getSubject(): ?SplitJourneySubject
    {
        return $this->subject;
    }

    /**
     * @param null|SplitJourneySubject $subject
     */
    public function setSubject($subject): self
    {
        $this->subject = $subject;
        return $this;
    }
}