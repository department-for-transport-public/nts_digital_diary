<?php

namespace App\DataFixtures\Definition;

use App\Entity\Embeddable\Distance;

abstract class StageDefinition
{
    protected ?string $methodOther = null;

    public function __construct(protected int $number, protected string $method, protected Distance $distance, protected int $travelTime, protected int $adultCount, protected int $childCount)
    {
    }

    public function getNumber(): int {
        return $this->number;
    }

    public function getMethod(): string {
        return $this->method;
    }

    public function getMethodOther(): ?string {
        return $this->methodOther ?? null;
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