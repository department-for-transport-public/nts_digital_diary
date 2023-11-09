<?php

namespace App\Security\OneTimePassword;

use App\Entity\AreaPeriod;
use App\Entity\Household;

interface OtpUserInterface
{
    public function getHousehold(): ?Household;
    public function getAreaPeriod(): ?AreaPeriod;
}