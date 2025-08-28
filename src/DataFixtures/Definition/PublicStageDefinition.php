<?php

namespace App\DataFixtures\Definition;

use App\Entity\Embeddable\Distance;

class PublicStageDefinition extends StageDefinition
{
    public function __construct(int $number, string $method, Distance $distance, int $travelTime, int $adultCount, int $childCount, protected ?string $ticketCost = null, protected ?string $ticketType = null, protected ?int $boardingCount = null, ?string $methodOther = null) {
        parent::__construct($number, $method, $distance, $travelTime, $adultCount, $childCount);

        $this->methodOther = $methodOther;
    }

    public function getTicketType(): ?string {
        return $this->ticketType;
    }

    public function getTicketCost(): ?string {
        return $this->ticketCost;
    }

    public function getBoardingCount(): ?int {
        return $this->boardingCount;
    }
}