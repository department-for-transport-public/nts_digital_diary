<?php


namespace App\FormWizard\OnBoarding;


use App\Entity\DiaryKeeper;
use App\FormWizard\AbstractFormWizardState;

class DiaryKeeperState extends AbstractFormWizardState
{
    const STATE_DETAILS = 'details';
    const STATE_IDENTITY = 'identity';
    const STATE_ADD_ANOTHER = 'add-another';
    const STATE_FINISH = 'finish';

    const TRANSITION_DETAILS_TO_IDENTITY = 'details-to-identity';
    const TRANSITION_DETAILS_TO_FINISH = 'details-to-finish';
    const TRANSITION_IDENTITY_TO_ADD_ANOTHER = 'identity-to-add-another';
    const TRANSITION_IDENTITY_TO_FINISH = 'identity-to-finish';

    // This workflow is used by both onboarding and household maintenance (adding diary keepers via admin after
    // onboarding), and as such needs a separate transition to return to the correct dashboard after finishing.
    const TRANSITION_IDENTITY_TO_FINISH_HOUSEHOLD_MAINTENANCE = 'identity-to-finish-household-maintenance';
    const TRANSITION_ADD_ANOTHER = 'add-another';
    const TRANSITION_FINISH = 'finish';

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