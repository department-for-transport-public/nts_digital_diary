<?php

namespace App\FormWizard\TravelDiary;

use Ghost\GovUkCoreBundle\FormWizard\AbstractFormWizardState;
use App\Entity\Journey\Journey as JourneyEntity;

class JourneyState extends AbstractFormWizardState
{
    public const STATE_PURPOSE = 'purpose';
    public const STATE_LOCATIONS = 'locations';
    public const STATE_TIMES = 'times';
    public const STATE_FINISH = 'finish';

    public const TRANSITION_LOCATIONS_TO_TIMES = 'locations-to-times';
    public const TRANSITION_TIMES_TO_FINISH = 'times-to-finish';
    public const TRANSITION_TIMES_TO_PURPOSE = 'times-to-purpose';
    public const TRANSITION_PURPOSE_TO_FINISH = 'purpose-to-finish';

    protected ?JourneyEntity $subject = null;

    public function getSubject(): ?JourneyEntity
    {
        return $this->subject;
    }

    /**
     * @param null|JourneyEntity $subject
     */
    public function setSubject($subject): self
    {
        $this->subject = $subject;
        return $this;
    }
}