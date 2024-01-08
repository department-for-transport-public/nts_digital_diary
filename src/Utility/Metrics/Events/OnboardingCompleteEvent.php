<?php

namespace App\Utility\Metrics\Events;

class OnboardingCompleteEvent extends AbstractEvent
{
    public function __construct(string $householdSerial)
    {
        $this->diarySerial = $householdSerial;
    }

    public function getName(): string
    {
        return 'Onboarding: complete';
    }
}