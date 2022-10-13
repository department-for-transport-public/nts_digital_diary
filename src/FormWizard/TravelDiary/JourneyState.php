<?php

namespace App\FormWizard\TravelDiary;

use App\Entity\Journey\Journey as JourneyEntity;
use App\Entity\Journey\Journey;
use App\FormWizard\AbstractFormWizardState;

class JourneyState extends AbstractFormWizardState
{
    const STATE_PURPOSE = 'purpose';
    const STATE_LOCATIONS = 'locations';
    const STATE_TIMES = 'times';
    const STATE_FINISH = 'finish';

    const TRANSITION_LOCATIONS_TO_TIMES = 'locations-to-times';
    const TRANSITION_TIMES_TO_FINISH = 'times-to-finish';
    const TRANSITION_TIMES_TO_PURPOSE = 'times-to-purpose';
    const TRANSITION_PURPOSE_TO_FINISH = 'purpose-to-finish';

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