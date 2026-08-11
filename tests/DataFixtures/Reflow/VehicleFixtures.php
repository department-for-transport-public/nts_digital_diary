<?php

namespace App\Tests\DataFixtures\Reflow;

use App\DataFixtures\NtsFixtures;

class VehicleFixtures extends \App\Tests\DataFixtures\VehicleFixtures
{
    public function getDependencies(): array
    {
        return [UserFixtures::class, HouseholdFixtures::class, NtsFixtures::class];
    }
}
