<?php

namespace App\Tests\DataFixtures\Reflow;

class JourneyFixtures extends \App\Tests\DataFixtures\JourneyFixtures
{
    public function getDependencies(): array
    {
        return [UserFixtures::class];
    }
}
