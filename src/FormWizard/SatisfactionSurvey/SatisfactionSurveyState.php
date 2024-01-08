<?php

namespace App\FormWizard\SatisfactionSurvey;

use App\Entity\SatisfactionSurvey;
use App\FormWizard\AbstractFormWizardState;

class SatisfactionSurveyState extends AbstractFormWizardState
{
    const STATE_INTRODUCTION = 'introduction';
    const STATE_EASE_OF_USE = 'ease-of-use';
    const STATE_BURDEN_OF_USE = 'burden-of-use';
    const STATE_DIARY_COMPLETION = 'diary-completion';
    const STATE_TYPE_OF_DEVICES = 'type-of-devices';
    const STATE_PREFERRED_METHOD = 'preferred-method';
    const STATE_FINISH = 'finish';

    const TRANSITION_INTRO_TO_EASE_OF_USE = 'introduction-to-ease-of-use';
    const TRANSITION_EASE_OF_USE_TO_BURDEN = 'ease-of-use-to-burden';
    const TRANSITION_BURDEN_TO_TYPE_OF_DEVICES = 'burden-to-type-of-devices';
    const TRANSITION_TYPE_OF_DEVICES_TO_DIARY_COMPLETION = 'type-of-devices-to-diary-completion';
    const TRANSITION_DIARY_COMPLETION_TO_PREFERRED_METHOD = 'diary-completion-to-preferred-method';
    const TRANSITION_PREFERRED_METHOD_TO_FINISH = 'preferred-method-to-finish';

    protected ?SatisfactionSurvey $subject = null;

    public function getSubject(): ?SatisfactionSurvey
    {
        return $this->subject;
    }

    public function setSubject($subject): self
    {
        $this->subject = $subject;
        return $this;
    }
}