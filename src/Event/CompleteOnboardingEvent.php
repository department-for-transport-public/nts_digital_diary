<?php

namespace App\Event;

use App\Entity\Household;
use Symfony\Contracts\EventDispatcher\Event;

class CompleteOnboardingEvent extends Event
{
    public function __construct(protected readonly Household $household) {}

    public function getHousehold(): Household
    {
        return $this->household;
    }
}