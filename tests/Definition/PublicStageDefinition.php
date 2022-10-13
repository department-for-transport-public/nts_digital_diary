<?php

namespace App\Tests\Definition;

use App\Entity\Distance;

class PublicStageDefinition extends StageDefinition
{
    protected string $ticketType;
    protected int $ticketCost;
    protected int $boardingCount;

    public function __construct(int $number, string $method, Distance $distance, int $travelTime, int $adultCount, int $childCount, int $ticketCost, string $ticketType, int $boardingCount) {
        parent::__construct($number, $method, $distance, $travelTime, $adultCount, $childCount);

        $this->ticketCost = $ticketCost;
        $this->ticketType = $ticketType;
        $this->boardingCount = $boardingCount;
    }

    public function getTicketType(): string {
        return $this->ticketType;
    }

    public function getTicketCost(): int {
        return $this->ticketCost;
    }

    public function getBoardingCount(): int {
        return $this->boardingCount;
    }
}