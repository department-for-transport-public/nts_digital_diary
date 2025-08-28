<?php

namespace App\DataFixtures\Definition;

use App\Entity\Embeddable\Distance;

class PrivateStageDefinition extends StageDefinition
{
    public function __construct(int $number, string $method, Distance $distance, int $travelTime, int $adultCount, int $childCount, protected ?bool $isDriver, protected ?string $parkingCost, protected int|string $vehicle) {
        parent::__construct($number, $method, $distance, $travelTime, $adultCount, $childCount);
   }

    public function getVehicle(): int|string {
        return $this->vehicle;
    }

    public function getIsDriver(): ?bool {
        return $this->isDriver;
    }

    public function getParkingCost(): ?string {
        return $this->parkingCost;
    }
}