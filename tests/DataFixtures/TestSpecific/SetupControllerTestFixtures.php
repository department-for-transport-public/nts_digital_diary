<?php

namespace App\Tests\DataFixtures\TestSpecific;

use App\DataFixtures\NtsFixtures;
use App\Entity\AreaPeriod;
use App\Entity\DiaryKeeper;
use App\Entity\Household;
use App\Entity\Interviewer;
use App\Entity\User;
use App\Tests\DataFixtures\AreaPeriodFixtures;
use App\Utility\TravelDiary\SerialHelper;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class SetupControllerTestFixtures extends AbstractFixture implements DependentFixtureInterface
{
    use CreateUserTrait;

    public function load(ObjectManager $manager): void
    {
        $areaPeriod = $this->getReference('area-period:1', AreaPeriod::class);

        $household = (new Household())
            ->setHouseholdNumber(9)
            ->setAddressNumber(9)
            ->setAreaPeriod($areaPeriod)
            ->setDiaryWeekStartDate(new \DateTime('2021-11-22'))
            ->setIsOnboardingComplete(false);

        $household->setCheckLetter(SerialHelper::getCheckLetter(
            $household->getAreaPeriod()->getArea(),
            $household->getAddressNumber(),
            $household->getHouseholdNumber()
        ));

        $manager->persist($household);

        // Diary keepers
        $dkNoPassword = (new DiaryKeeper())
            ->setName('Setup Test DK (no password)')
            ->setIsAdult(true)
            ->setNumber(1)
            ->setMediaType(DiaryKeeper::MEDIA_TYPE_DIGITAL)
            ->setHousehold($household)
            ->setUser($this->createUser($manager, 'dk-setup-nopass@example.com', null)->setHasLoggedIn(true));

        $dkWithPassword = (new DiaryKeeper())
            ->setName('Setup Test DK (with password)')
            ->setIsAdult(true)
            ->setNumber(2)
            ->setMediaType(DiaryKeeper::MEDIA_TYPE_DIGITAL)
            ->setHousehold($household)
            ->setUser($this->createUser($manager, 'dk-setup-pass@example.com')->setHasLoggedIn(true));

        $dkNoLogin = (new DiaryKeeper())
            ->setName('Setup Test DK (no login)')
            ->setIsAdult(true)
            ->setNumber(3)
            ->setMediaType(DiaryKeeper::MEDIA_TYPE_DIGITAL)
            ->setHousehold($household)
            ->setUser($this->createUser($manager, User::generateNoLoginPlaceholder(), null)->setHasLoggedIn(true));

        $manager->persist($dkNoPassword);
        $manager->persist($dkWithPassword);
        $manager->persist($dkNoLogin);

        $this->setReference('setup-test:dk-no-password', $dkNoPassword);
        $this->setReference('setup-test:dk-with-password', $dkWithPassword);
        $this->setReference('setup-test:dk-no-login', $dkNoLogin);

        // Mark onboarding complete after persisting DKs to avoid AddDiaryKeeperSubscriber
        $manager->flush();
        $household->setIsOnboardingComplete(true);
        $manager->flush();

        // Interviewers
        $intNoPassword = (new Interviewer())
            ->setName('Setup Test Interviewer (no password)')
            ->setSerialId(901)
            ->setUser($this->createUser($manager, 'int-setup-nopass@example.com', null)->setHasLoggedIn(true));

        $intWithPassword = (new Interviewer())
            ->setName('Setup Test Interviewer (with password)')
            ->setSerialId(902)
            ->setUser($this->createUser($manager, 'int-setup-pass@example.com')->setHasLoggedIn(true));

        $manager->persist($intNoPassword);
        $manager->persist($intWithPassword);

        $this->setReference('setup-test:int-no-password', $intNoPassword);
        $this->setReference('setup-test:int-with-password', $intWithPassword);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [AreaPeriodFixtures::class, NtsFixtures::class];
    }
}
