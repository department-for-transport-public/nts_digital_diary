<?php

namespace App\DataFixtures\Definition;

use App\Entity\Embeddable\Distance;

class PublicStageDefinition extends StageDefinition
{
    protected string $ticketType;
    protected ?string $ticketCost;
    protected int $boardingCount;

    public function __construct(int $number, string $method, Distance $distance, int $travelTime, int $adultCount, int $childCount, ?string $ticketCost, string $ticketType, int $boardingCount, ?string $methodOther = null) {
        parent::__construct($number, $method, $distance, $travelTime, $adultCount, $childCount);

        $this->ticketCost = $ticketCost;
        $this->ticketType = $ticketType;
        $this->boardingCount = $boardingCount;

        $this->methodOther = $methodOther;
    }

    public function getTicketType(): string {
        return $this->ticketType;
    }

    public function getTicketCost(): ?string {
        return $this->ticketCost;
    }

    public function getBoardingCount(): int {
        return $this->boardingCount;
    }
}