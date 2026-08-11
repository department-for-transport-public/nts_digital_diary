<?php

namespace App\Tests\DataFixtures\TestSpecific;

use App\DataFixtures\NtsFixtures;
use App\Entity\AreaPeriod;
use App\Entity\DiaryKeeper;
use App\Entity\Household;
use App\Entity\User;
use App\Tests\DataFixtures\AreaPeriodFixtures;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class MutualProxyHouseholdFixtures extends AbstractFixture implements DependentFixtureInterface
{
    use CreateUserTrait;

    public function load(ObjectManager $manager): void
    {
        $areaPeriod = $this->getReference('area-period:2', AreaPeriod::class);

        $household = (new Household())
            ->setHouseholdNumber(1)
            ->setAddressNumber(8)
            ->setAreaPeriod($areaPeriod)
            ->setDiaryWeekStartDate(new \DateTime('2021-11-22'))
            ->setIsJourneySharingEnabled(true)
            ->setIsOnboardingComplete(true);

        $this->addReference('household:mutual-proxy', $household);
        $manager->persist($household);

        $dkA = (new DiaryKeeper())
            ->setName('Alex')
            ->setNumber(1)
            ->setIsAdult(true)
            ->setMediaType(DiaryKeeper::MEDIA_TYPE_DIGITAL)
            ->setUser($this->createUser($manager, 'mutual-proxy.diary-keeper.a@example.com', 'password'));

        $dkB = (new DiaryKeeper())
            ->setName('Blake')
            ->setNumber(2)
            ->setIsAdult(true)
            ->setMediaType(DiaryKeeper::MEDIA_TYPE_DIGITAL)
            ->setUser($this->createUser($manager, 'mutual-proxy.diary-keeper.b@example.com', 'access'));

        $household->addDiaryKeeper($dkA);
        $household->addDiaryKeeper($dkB);

        $dkA->addActingAsProxyFor($dkB);
        $dkB->addActingAsProxyFor($dkA);

        $manager->persist($dkA);
        $manager->persist($dkB);

        $this->addReference('diary-keeper:mutual-proxy:a', $dkA);
        $this->addReference('diary-keeper:mutual-proxy:b', $dkB);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [NtsFixtures::class, AreaPeriodFixtures::class];
    }
}
