<?php

namespace App\Event;

use App\Entity\Household;
use Symfony\Contracts\EventDispatcher\Event;

class SubmitHouseholdEvent extends Event
{
    public function __construct(private readonly Household $household) {}

    public function getHousehold(): Household
    {
        return $this->household;
    }
}