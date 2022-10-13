<?php

namespace App\Entity;

class VehicleOtherPlaceholder
{
    protected string $friendlyName;

    public function __construct(string $friendlyName)
    {
        $this->friendlyName = $friendlyName;
    }


    public function getFriendlyName(): string
    {
        return $this->friendlyName;
    }
}