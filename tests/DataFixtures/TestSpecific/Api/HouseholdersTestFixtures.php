<?php

namespace App\Tests\DataFixtures\TestSpecific\Api;

use App\Entity\DiaryKeeper;
use App\Entity\Household;
use App\Tests\DataFixtures\HouseholdFixtures;
use App\Tests\DataFixtures\StageFixtures;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class HouseholdersTestFixtures extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $householdOnboarded = $this->getReference('household:onboarded', Household::class);

        $stateMap = [
            1 => DiaryKeeper::STATE_NEW,
            2 => DiaryKeeper::STATE_APPROVED,
            3 => DiaryKeeper::STATE_IN_PROGRESS,
            4 => DiaryKeeper::STATE_COMPLETED,
        ];

        $householdOnboarded->getDiaryKeepers()->map(fn (DiaryKeeper $dk): DiaryKeeper => $dk->setDiaryState($stateMap[$dk->getNumber()]));
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            StageFixtures::class,
            HouseholdFixtures::class,
        ];
    }
}
