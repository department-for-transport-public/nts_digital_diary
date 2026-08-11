<?php

namespace App\Tests\DataFixtures\Reflow;

class StageFixtures extends \App\Tests\DataFixtures\StageFixtures
{
    public function getDependencies(): array
    {
        return [JourneyFixtures::class, VehicleFixtures::class];
    }
}

