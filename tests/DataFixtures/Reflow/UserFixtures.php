<?php

namespace App\Tests\DataFixtures\Reflow;

use App\DataFixtures\NtsFixtures;

class UserFixtures extends \App\Tests\DataFixtures\UserFixtures
{
    public function getDependencies(): array
    {
        return [HouseholdFixtures::class, NtsFixtures::class];
    }
}