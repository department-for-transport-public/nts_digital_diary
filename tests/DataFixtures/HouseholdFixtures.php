<?php

namespace App\Tests\DataFixtures;

use App\DataFixtures\NtsFixtures;
use App\Entity\AreaPeriod;
use App\Entity\Household;
use App\Utility\TravelDiary\SerialHelper;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class HouseholdFixtures extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $areaPeriod = $this->getReference('area-period:1', AreaPeriod::class);

        $householdNotOnboarded = (new Household())
            ->setHouseholdNumber(1)
            ->setAddressNumber(6)
            ->setAreaPeriod($areaPeriod)
            ->setDiaryWeekStartDate(new \DateTime('2021-11-15'))
            ->setIsOnboardingComplete(false);

        static::setCheckLetter($householdNotOnboarded);

        $this->addReference('household:not-onboarded', $householdNotOnboarded);
        $manager->persist($householdNotOnboarded);

        $householdOnboarded = (new Household())
            ->setHouseholdNumber(2)
            ->setAddressNumber(8)
            ->setAreaPeriod($areaPeriod)
            ->setDiaryWeekStartDate(new \DateTime('2021-11-22'))
            ->setIsOnboardingComplete(true);

        static::setCheckLetter($householdOnboarded);

        $this->addReference('household:onboarded', $householdOnboarded);
        $manager->persist($householdOnboarded);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [AreaPeriodFixtures::class, NtsFixtures::class];
    }

    public static function setCheckLetter(Household $household): void
    {
        $household->setCheckLetter(SerialHelper::getCheckLetter(
            $household->getAreaPeriod()->getArea(),
            $household->getAddressNumber(),
            $household->getHouseholdNumber()
        ));
    }
}
