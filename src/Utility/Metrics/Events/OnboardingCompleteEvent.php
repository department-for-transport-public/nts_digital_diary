<?php

namespace App\Utility\Metrics\Events;

use App\EventSubscriber\Onboarding\OnboardingType;

class OnboardingCompleteEvent extends AbstractEvent
{
    public function __construct(
        string $householdSerial,
        OnboardingType $onboardingType,
    )
    {
        $this->diarySerial = $householdSerial;
        $this->metadata['onboardingType'] = $onboardingType->value;
    }

    public function getName(): string
    {
        return 'Onboarding: complete';
    }
}
