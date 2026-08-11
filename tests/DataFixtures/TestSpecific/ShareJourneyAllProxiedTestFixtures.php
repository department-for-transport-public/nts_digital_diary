<?php

namespace App\Tests\DataFixtures\TestSpecific;

use App\Entity\DiaryKeeper;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ShareJourneyAllProxiedTestFixtures extends AbstractFixture implements DependentFixtureInterface
{
    public function getDependencies(): array
    {
        return [ShareJourneyTestFixtures::class];
    }

    public function load(ObjectManager $manager): void
    {
        $sourceDK = $this->getReference('diary-keeper:adult', DiaryKeeper::class);
        $childDK = $this->getReference('diary-keeper:child', DiaryKeeper::class);
        $noPasswordDK = $this->getReference('diary-keeper:no-password', DiaryKeeper::class);

        $sourceDK->addActingAsProxyFor($childDK);
        $sourceDK->addActingAsProxyFor($noPasswordDK);

        $manager->flush();
    }
}
