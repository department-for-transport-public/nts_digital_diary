<?php

namespace App\Tests\DataFixtures\Reflow;

use App\DataFixtures\NtsFixtures;

class HouseholdFixtures extends \App\Tests\DataFixtures\HouseholdFixtures
{
    public function getDependencies(): array
    {
        return [AreaPeriodFixtures::class, NtsFixtures::class];
    }
}
