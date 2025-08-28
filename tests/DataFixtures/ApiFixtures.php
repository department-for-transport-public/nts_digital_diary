<?php

namespace App\Tests\DataFixtures;

use App\Entity\DiaryKeeper;
use App\Entity\Household;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ApiFixtures extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $householdOnboarded = $this->getReference('household:onboarded', Household::class);

        $householdOnboarded->getDiaryKeepers()->map(fn (DiaryKeeper $dk): DiaryKeeper =>
            $dk->setDiaryState(DiaryKeeper::STATE_APPROVED)
        );

        $householdOnboarded
            ->setSubmittedAt(new \DateTime('2021-11-22 13:30'))
            ->setSubmittedBy('T1000');

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [StageFixtures::class, HouseholdFixtures::class];
    }
}
