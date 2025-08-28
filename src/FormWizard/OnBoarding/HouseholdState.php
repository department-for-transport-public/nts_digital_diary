<?php


namespace App\FormWizard\OnBoarding;


use Ghost\GovUkCoreBundle\FormWizard\AbstractFormWizardState;
use App\Entity\Household;

class HouseholdState extends AbstractFormWizardState
{
    public const STATE_INTRODUCTION = 'introduction';
    public const STATE_DETAILS = 'details';
    public const STATE_FINISH = 'finish';

    public const TRANSITION_INTRODUCTION_TO_DETAILS = 'introduction-to-details';
    public const TRANSITION_DETAILS_TO_FINISH = 'details-to-finish';

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