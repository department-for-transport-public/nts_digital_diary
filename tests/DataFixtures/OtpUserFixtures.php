<?php

namespace App\Tests\DataFixtures;

use App\DataFixtures\NtsFixtures;
use App\Entity\AreaPeriod;
use App\Entity\Household;
use App\Entity\OtpUser;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class OtpUserFixtures extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $areaPeriod = $this->getReference('area-period:1');
        $household = $this->getReference('household:not-onboarded');

        assert($areaPeriod instanceof AreaPeriod);
        assert($household instanceof Household);

        $otpUserOne = (new OtpUser())
            ->setUserIdentifier('1234567890')
            ->setAreaPeriod($areaPeriod);

        $otpUserTwo = (new OtpUser())
            ->setUserIdentifier('2345678901')
            ->setAreaPeriod($areaPeriod)
            ->setHousehold($household);

        $this->setReference('user:otp', $otpUserOne);
        $this->setReference('user:otp:partially-onboarded', $otpUserTwo);

        $manager->persist($otpUserOne);
        $manager->persist($otpUserTwo);
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [AreaPeriodFixtures::class, HouseholdFixtures::class, NtsFixtures::class];
    }
}