<?php

namespace App\Tests\DataFixtures\TestSpecific;

use App\Entity\DiaryKeeper;
use App\Entity\Household;
use App\Tests\DataFixtures\HouseholdFixtures;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class DiaryKeeperForVehicleOnboardingFixtures extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        /** @var Household $household */
        $household = $this->getReference('household:not-onboarded');

        $dk1 = (new DiaryKeeper())
            ->setName('Alice')
            ->setNumber(1)
            ->setIsAdult(true)
            ->setMediaType(DiaryKeeper::MEDIA_TYPE_PAPER)
            ;
        $dk2 = (new DiaryKeeper())
            ->setName('Bob')
            ->setNumber(2)
            ->setIsAdult(true)
            ->setMediaType(DiaryKeeper::MEDIA_TYPE_PAPER)
            ;

        $this->addReference('diary-keeper:not-onboarded:1', $dk1);
        $this->addReference('diary-keeper:not-onboarded:2', $dk2);

        $household->addDiaryKeeper($dk1);
        $household->addDiaryKeeper($dk2);

        $manager->persist($dk1);
        $manager->persist($dk2);
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [HouseholdFixtures::class];
    }
}