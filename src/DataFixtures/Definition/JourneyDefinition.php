<?php

namespace App\DataFixtures\Definition;

class JourneyDefinition
{
    public function __construct(
        protected int $dayNumber,
        protected string $startLocation,
        protected string $startTime,
        protected string $endLocation,
        protected string $endTime,
        protected string $purpose,
        protected array $stageDefinitions
    ) {}

    public function getDayNumber(): int
    {
        return $this->dayNumber;
    }

    public function getStartLocation(): string {
        return $this->startLocation;
    }

    public function getStartTime(): string {
        return $this->startTime;
    }

    public function getEndLocation(): string {
        return $this->endLocation;
    }

    public function getEndTime(): string {
        return $this->endTime;
    }

    public function getPurpose(): string {
        return $this->purpose;
    }

    /**
     * @return StageDefinition[]|array
     */
    public function getStageDefinitions(): array {
        return $this->stageDefinitions;
    }
}