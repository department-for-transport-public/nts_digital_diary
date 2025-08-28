<?php

namespace App\Tests\DataFixtures\TestSpecific;

use App\DataFixtures\NtsFixtures;
use App\Entity\AreaPeriod;
use App\Entity\OtpUser;
use App\Tests\DataFixtures\AreaPeriodFixtures;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class IntroductionBackCrashFixtures extends Fixture implements DependentFixtureInterface
{

    public function load(ObjectManager $manager): void
    {
        $areaPeriod = $this->getReference('area-period:1', AreaPeriod::class);

        $otpUserOne = (new OtpUser())
            ->setUserIdentifier('1234567890')
            ->setAreaPeriod($areaPeriod);

        $manager->persist($otpUserOne);
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [AreaPeriodFixtures::class, NtsFixtures::class];
    }
}
