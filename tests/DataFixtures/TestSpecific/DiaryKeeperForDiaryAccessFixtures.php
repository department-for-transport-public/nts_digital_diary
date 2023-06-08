<?php

namespace App\Tests\DataFixtures\TestSpecific;

use App\DataFixtures\NtsFixtures;
use App\Entity\AreaPeriod;
use App\Entity\DiaryKeeper;
use App\Entity\Household;
use App\Entity\User;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class DiaryKeeperForDiaryAccessFixtures extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        /** @var AreaPeriod $areaPeriod */
        $areaPeriod = $this->getReference('area-period:2');
        $household = (new Household())
            ->setHouseholdNumber(5)
            ->setAddressNumber(8)
            ->setAreaPeriod($areaPeriod)
            ->setDiaryWeekStartDate(new \DateTime('2021-11-22'))
            ->setIsJourneySharingEnabled(true)
            ->setIsOnboardingComplete(true);
        $this->addReference('household:access', $household);
        $manager->persist($household);

        $dk1 = (new DiaryKeeper())
            ->setName('Charlie')
            ->setNumber(1)
            ->setIsAdult(true)
            ->setMediaType(DiaryKeeper::MEDIA_TYPE_DIGITAL)
            ->setUser($this->createUser($manager, 'diary-keeper-access@example.com', 'access'));
            ;
        $dk2 = (new DiaryKeeper())
            ->setName('David')
            ->setNumber(2)
            ->setIsAdult(true)
            ->setMediaType(DiaryKeeper::MEDIA_TYPE_PAPER)
            ;

        $this->addReference('diary-keeper:access', $dk1);

        $household->addDiaryKeeper($dk1);
        $household->addDiaryKeeper($dk2);

        $manager->persist($dk1);
        $manager->persist($dk2);
        $manager->flush();
    }

    protected function createUser(ObjectManager $manager, string $email, ?string $password='password'): User
    {
        $user = (new User())
            ->setUsername($email);

        if ($password) {
            $user->setPlainPassword($password);
        }

        $manager->persist($user);
        return $user;
    }

    public function getDependencies(): array
    {
        return [NtsFixtures::class];
    }
}