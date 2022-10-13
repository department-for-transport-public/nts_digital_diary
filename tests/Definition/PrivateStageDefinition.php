<?php

namespace App\Tests\Definition;

use App\Entity\Distance;

class PrivateStageDefinition extends StageDefinition
{
    protected string $vehicle;
    protected ?bool $isDriver;
    protected ?int $parkingCost;

    public function __construct(int $number, string $method, Distance $distance, int $travelTime, int $adultCount, int $childCount, bool $isDriver, ?int $parkingCost, string $vehicle) {
        parent::__construct($number, $method, $distance, $travelTime, $adultCount, $childCount);

        $this->isDriver = $isDriver;
        $this->parkingCost = $parkingCost;
        $this->vehicle = $vehicle;
   }

    public function getVehicle(): string {
        return $this->vehicle;
    }

    public function getIsDriver(): ?bool {
        return $this->isDriver;
    }

    public function getParkingCost(): ?int {
        return $this->parkingCost;
    }
}