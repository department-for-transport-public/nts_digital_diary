<?php

namespace App\Tests\Definition;

use App\Entity\Distance;

class OtherStageDefinition extends StageDefinition
{
    public function __construct(int $number, string $method, Distance $distance, int $travelTime, int $adultCount, int $childCount) {
        parent::__construct($number, $method, $distance, $travelTime, $adultCount, $childCount);
    }
}