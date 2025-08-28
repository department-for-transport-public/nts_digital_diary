<?php

namespace App\Event;

use App\Entity\Household;
use App\EventSubscriber\Onboarding\OnboardingType;
use Symfony\Contracts\EventDispatcher\Event;

class CompleteOnboardingEvent extends Event
{
    public function __construct(
        protected Household $household,
        protected OnboardingType $onboardingType,
    ) {}

    public function getHousehold(): Household
    {
        return $this->household;
    }

    public function getOnboardingType(): OnboardingType
    {
        return $this->onboardingType;
    }
}
