<?php

namespace App\Tests\Definition;

use App\Entity\Distance;

abstract class StageDefinition
{
    protected int $number;
    protected string $method;

    protected Distance $distance;
    protected int $travelTime;
    protected int $adultCount;
    protected int $childCount;

    public function __construct(int $number, string $method, Distance $distance, int $travelTime, int $adultCount, int $childCount)
    {
        $this->number = $number;
        $this->method = $method;
        $this->distance = $distance;
        $this->travelTime = $travelTime;
        $this->adultCount = $adultCount;
        $this->childCount = $childCount;
    }

    public function getNumber(): int {
        return $this->number;
    }

    public function getMethod(): string {
        return $this->method;
    }

    public function getDistance(): Distance {
        return $this->distance;
    }

    public function getTravelTime(): int {
        return $this->travelTime;
    }

    public function getAdultCount(): int {
        return $this->adultCount;
    }

    public function getChildCount(): int {
        return $this->childCount;
    }
}