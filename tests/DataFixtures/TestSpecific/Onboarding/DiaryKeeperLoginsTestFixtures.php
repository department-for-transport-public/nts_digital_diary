<?php

namespace App\Tests\DataFixtures\TestSpecific\Onboarding;

use App\Entity\AreaPeriod;
use App\Entity\Household;
use App\Tests\DataFixtures\AreaPeriodFixtures;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class DiaryKeeperLoginsTestFixtures extends AbstractOnboardingTestFixtures implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $this->manager = $manager;

        $householdOne = (new Household())
            ->setAddressNumber(1)
            ->setHouseholdNumber(1);

        $householdTwo = (new Household())
            ->setAddressNumber(2)
            ->setHouseholdNumber(1);

        $areaPeriod = $this->getReference('area-period:1', AreaPeriod::class);
        $areaPeriod->addHousehold($householdOne);
        $areaPeriod->addHousehold($householdTwo);

        $this->createDiaryKeeper($householdOne, 'Wilbur', null, 1, true);
        $this->createDiaryKeeper($householdOne, 'Brave', null, 2, true);
        $this->createDiaryKeeper($householdTwo, 'Kosh', 'kosh@example.com', 1, true);

        $interviewer = $this->createInterviewer('Poppy', 'poppy@example.com', '1');

        $this->createTrainingUser('winnie@example.com', $interviewer);

        $manager->persist($householdOne);
        $manager->persist($householdTwo);
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [AreaPeriodFixtures::class];
    }
}
