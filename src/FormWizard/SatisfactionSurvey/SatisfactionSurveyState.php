<?php

namespace App\FormWizard\SatisfactionSurvey;

use Ghost\GovUkCoreBundle\FormWizard\AbstractFormWizardState;
use App\Entity\SatisfactionSurvey;

class SatisfactionSurveyState extends AbstractFormWizardState
{
    public const STATE_INTRODUCTION = 'introduction';
    public const STATE_EASE_OF_USE = 'ease-of-use';
    public const STATE_BURDEN_OF_USE = 'burden-of-use';
    public const STATE_DIARY_COMPLETION = 'diary-completion';
    public const STATE_TYPE_OF_DEVICES = 'type-of-devices';
    public const STATE_PREFERRED_METHOD = 'preferred-method';
    public const STATE_FINISH = 'finish';

    public const TRANSITION_INTRO_TO_EASE_OF_USE = 'introduction-to-ease-of-use';
    public const TRANSITION_EASE_OF_USE_TO_BURDEN = 'ease-of-use-to-burden';
    public const TRANSITION_BURDEN_TO_TYPE_OF_DEVICES = 'burden-to-type-of-devices';
    public const TRANSITION_TYPE_OF_DEVICES_TO_DIARY_COMPLETION = 'type-of-devices-to-diary-completion';
    public const TRANSITION_DIARY_COMPLETION_TO_PREFERRED_METHOD = 'diary-completion-to-preferred-method';
    public const TRANSITION_PREFERRED_METHOD_TO_FINISH = 'preferred-method-to-finish';

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