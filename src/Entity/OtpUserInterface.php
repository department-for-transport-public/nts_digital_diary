<?php

namespace App\Entity;

interface OtpUserInterface
{
    public function getHousehold(): ?Household;
    public function getAreaPeriod(): ?AreaPeriod;
}