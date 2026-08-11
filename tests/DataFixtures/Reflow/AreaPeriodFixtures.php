<?php

namespace App\Tests\DataFixtures\Reflow;

use App\DataFixtures\NtsFixtures;
use App\Entity\AreaPeriod;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class AreaPeriodFixtures extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $areaPeriodOne = (new AreaPeriod())
            ->setArea("111113")
            ->setYear(2021)
            ->setMonth(11);

        $this->addReference('area-period:1', $areaPeriodOne);

        $manager->persist($areaPeriodOne);
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [NtsFixtures::class];
    }
}

