<?php


namespace App\FormWizard\OnBoarding;


use App\Entity\Household;
use App\FormWizard\AbstractFormWizardState;

class HouseholdState extends AbstractFormWizardState
{
    const STATE_INTRODUCTION = 'introduction';
    const STATE_DETAILS = 'details';
    const STATE_FINISH = 'finish';

    const TRANSITION_INTRODUCTION_TO_DETAILS = 'introduction-to-details';
    const TRANSITION_DETAILS_TO_FINISH = 'details-to-finish';

    protected Household $household;

    public function getSubject(): ?Household
    {
        return $this->household ?? null;
    }

    public function setSubject($subject): self
    {
        $this->household = $subject;
        return $this;
    }
}