<?php

namespace App\DataFixtures\Definition;

use App\Entity\Embeddable\Distance;

class PrivateStageDefinition extends StageDefinition
{
    protected int|string $vehicle;
    protected ?bool $isDriver;
    protected ?string $parkingCost;

    public function __construct(int $number, string $method, Distance $distance, int $travelTime, int $adultCount, int $childCount, bool $isDriver, ?string $parkingCost, int|string $vehicle) {
        parent::__construct($number, $method, $distance, $travelTime, $adultCount, $childCount);

        $this->isDriver = $isDriver;
        $this->parkingCost = $parkingCost;
        $this->vehicle = $vehicle;
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