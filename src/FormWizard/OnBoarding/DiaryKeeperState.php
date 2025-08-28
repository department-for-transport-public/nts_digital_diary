<?php


namespace App\FormWizard\OnBoarding;


use Ghost\GovUkCoreBundle\FormWizard\AbstractFormWizardState;
use App\Entity\DiaryKeeper;

class DiaryKeeperState extends AbstractFormWizardState
{
    public const STATE_DETAILS = 'details';
    public const STATE_IDENTITY = 'identity';
    public const STATE_ADD_ANOTHER = 'add-another';
    public const STATE_FINISH = 'finish';

    public const TRANSITION_DETAILS_TO_IDENTITY = 'details-to-identity';
    public const TRANSITION_DETAILS_TO_FINISH = 'details-to-finish';
    public const TRANSITION_IDENTITY_TO_ADD_ANOTHER = 'identity-to-add-another';
    public const TRANSITION_IDENTITY_TO_FINISH = 'identity-to-finish';

    // This workflow is used by both onboarding and household maintenance (adding diary keepers via admin after
    // onboarding), and as such needs a separate transition to return to the correct dashboard after finishing.
    public const TRANSITION_IDENTITY_TO_FINISH_HOUSEHOLD_MAINTENANCE = 'identity-to-finish-household-maintenance';
    public const TRANSITION_ADD_ANOTHER = 'add-another';
    public const TRANSITION_FINISH = 'finish';

    protected DiaryKeeper $diaryKeeper;

    protected bool $addAnotherDisabled = false;

    public function getSubject(): ?DiaryKeeper
    {
        return $this->diaryKeeper ?? null;
    }

    public function setSubject($subject): self
    {
        $this->diaryKeeper = $subject;
        return $this;
    }

    public function setAddAnotherDisabled(bool $addAnotherDisabled): self
    {
        $this->addAnotherDisabled = $addAnotherDisabled;
        return $this;
    }

    public function isAddAnotherDisabled(): bool
    {
        return $this->addAnotherDisabled;
    }
}