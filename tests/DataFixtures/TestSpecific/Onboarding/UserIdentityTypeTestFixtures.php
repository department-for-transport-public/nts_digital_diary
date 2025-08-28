<?php

namespace App\Tests\DataFixtures\TestSpecific\Onboarding;

use App\Entity\AreaPeriod;
use App\Entity\Household;
use App\Tests\DataFixtures\AreaPeriodFixtures;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class UserIdentityTypeTestFixtures  extends AbstractOnboardingTestFixtures implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $this->manager = $manager;

        $household = (new Household())
            ->setAddressNumber(1)
            ->setHouseholdNumber(1);

        $this->setReference('household', $household);

        $areaPeriod = $this->getReference('area-period:1', AreaPeriod::class);
        $areaPeriod->addHousehold($household);

        $this->createDiaryKeeper($household, 'Mark', null, 1, true);
        $this->createDiaryKeeper($household, 'Linda', 'linda@example.com', 2, true);
        $this->createDiaryKeeper($household, 'Poppy', null, 3, false);

        $manager->persist($household);
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [AreaPeriodFixtures::class];
    }
}
