<?php

namespace App\Tests\Definition;

class JourneyDefinition
{
    protected string $startLocation;
    protected string $startTime;
    protected string $endLocation;
    protected string $endTime;
    protected string $purpose;

    protected array $stageDefinitions;

    public function __construct(string $startLocation, string $startTime, string $endLocation, string $endTime, string $purpose, array $stageDefinitions)
    {
        $this->startLocation = $startLocation;
        $this->startTime = $startTime;
        $this->endLocation = $endLocation;
        $this->endTime = $endTime;
        $this->purpose = $purpose;
        $this->stageDefinitions = $stageDefinitions;
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