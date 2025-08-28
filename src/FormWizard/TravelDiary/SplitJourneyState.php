<?php

namespace App\FormWizard\TravelDiary;

use Ghost\GovUkCoreBundle\FormWizard\AbstractFormWizardState;
use Symfony\Component\Validator\Constraints as Assert;

class SplitJourneyState extends AbstractFormWizardState
{
    public const STATE_INTRODUCTION = 'introduction';
    public const STATE_MIDPOINT = 'midpoint';
    public const STATE_PURPOSE = 'purpose';
    public const STATE_FINISH = 'finish';

    public const TRANSITION_INTRO_TO_MIDPOINT = 'introduction-to-midpoint';
    public const TRANSITION_MIDPOINT_TO_PURPOSE = 'midpoint-to-purpose';
    public const TRANSITION_MIDPOINT_TO_FINISH = 'midpoint-to-finish';
    public const TRANSITION_PURPOSE_TO_FINISH = 'purpose-to-finish';

    #[Assert\NotNull(message: 'wizard.split-journey.source-journey.not-null', groups: ['wizard.split-journey.source-journey'])]
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