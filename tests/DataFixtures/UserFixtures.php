<?php

namespace App\Tests\DataFixtures;

use App\DataFixtures\NtsFixtures;
use App\Entity\AreaPeriod;
use App\Entity\DiaryKeeper;
use App\Entity\Household;
use App\Entity\Interviewer;
use App\Entity\User;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class UserFixtures extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $areaPeriodOne = $this->getReference('area-period:1');
        $householdOnboarded = $this->getReference('household:onboarded');

        assert($areaPeriodOne instanceof AreaPeriod);
        assert($householdOnboarded instanceof Household);

        $diaryKeeperAdult = (new DiaryKeeper())
            ->setName('Test Diary Keeper (Adult)')
            ->setIsAdult(true)
            ->setNumber(1)
            ->setMediaType(DiaryKeeper::MEDIA_TYPE_DIGITAL)
            ->setHousehold($householdOnboarded)
            ->setUser($this->createUser($manager, 'diary-keeper-adult@example.com'));

        $diaryKeeperChild = (new DiaryKeeper())
            ->setName('Test Diary Keeper (Child)')
            ->setIsAdult(false)
            ->setNumber(2)
            ->setMediaType(DiaryKeeper::MEDIA_TYPE_DIGITAL)
            ->setHousehold($householdOnboarded)
            ->setUser($this->createUser($manager, 'diary-keeper-child@example.com'));

        $diaryKeeperProxied = (new DiaryKeeper())
            ->setName('Test Diary Keeper (Proxied)')
            ->setIsAdult(true)
            ->setNumber(3)
            ->setMediaType(DiaryKeeper::MEDIA_TYPE_DIGITAL)
            ->setHousehold($householdOnboarded)
            ->setUser($this->createUser($manager, 'diary-keeper-proxied@example.com'));

        $diaryKeeperAdult->addActingAsProxyFor($diaryKeeperProxied);

        $diaryKeeperNoPassword = (new DiaryKeeper())
            ->setName('Test Diary Keeper (No password set)')
            ->setIsAdult(true)
            ->setNumber(4)
            ->setMediaType(DiaryKeeper::MEDIA_TYPE_DIGITAL)
            ->setHousehold($householdOnboarded)
            ->setUser($this->createUser($manager, 'diary-keeper-no-password@example.com', null));

        $interviewer = (new Interviewer())
            ->setName('Test interviewer')
            ->setSerialId(101)
            ->setUser($this->createUser($manager, 'interviewer@example.com'));

        $areaPeriodOne->addInterviewer($interviewer);

        $manager->persist($diaryKeeperAdult);
        $manager->persist($diaryKeeperChild);
        $manager->persist($diaryKeeperProxied);
        $manager->persist($diaryKeeperNoPassword);
        $manager->persist($interviewer);

        $this->setReference('diary-keeper:adult', $diaryKeeperAdult);
        $this->setReference('diary-keeper:child', $diaryKeeperChild);
        $this->setReference('diary-keeper:proxied', $diaryKeeperProxied);
        $this->setReference('diary-keeper:no-password', $diaryKeeperNoPassword);
        $this->setReference('interviewer', $interviewer);

        $this->setReference('user:diary-keeper:adult', $diaryKeeperAdult->getUser());
        $this->setReference('user:diary-keeper:child', $diaryKeeperChild->getUser());
        $this->setReference('user:diary-keeper:proxied', $diaryKeeperProxied->getUser());
        $this->setReference('user:diary-keeper:no-password', $diaryKeeperNoPassword->getUser());
        $this->setReference('user:interviewer', $interviewer->getUser());

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
        return [HouseholdFixtures::class, NtsFixtures::class];
    }
}