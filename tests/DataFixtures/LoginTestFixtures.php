<?php

namespace App\Tests\DataFixtures;

use App\DataFixtures\NtsFixtures;
use App\Entity\AreaPeriod;
use App\Entity\DiaryKeeper;
use App\Entity\Household;
use App\Entity\Interviewer;
use App\Entity\User;
use App\Utility\InterviewerTraining\InterviewerTrainingHelper;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class LoginTestFixtures extends AbstractFixture implements DependentFixtureInterface
{
    public function __construct(protected InterviewerTrainingHelper $interviewerTrainingHelper)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $areaPeriod = (new AreaPeriod())
            ->setArea("111950")
            ->populateMonthAndYearFromArea();
        $manager->persist($areaPeriod);

        /** @var Interviewer $interviewer */
        $interviewer = $this->getReference('interviewer');
        $interviewer->addTrainingAreaPeriod($areaPeriod);

        $householdOnboardedOther = (new Household())
            ->setHouseholdNumber(3)
            ->setAddressNumber(1)
            ->setAreaPeriod($areaPeriod)
            ->setDiaryWeekStartDate(new \DateTime('2021-12-23'))
            ->setIsOnboardingComplete(true);

        $this->addReference('household:onboarded-other', $householdOnboardedOther);
        $manager->persist($householdOnboardedOther);

        $diaryKeeper = (new DiaryKeeper())
            ->setName('Diary Keeper')
            ->setIsAdult(true)
            ->setNumber(1)
            ->setMediaType(DiaryKeeper::MEDIA_TYPE_DIGITAL)
            ->setUser($this->createUser($manager, 'diary-keeper-nologin-test@example.com'))
            ->setHousehold($householdOnboardedOther);
        $manager->persist($diaryKeeper);

        $diaryKeeperProxiedNoLogin = (new DiaryKeeper())
            ->setName('Proxied NoLogion Diary Keeper')
            ->setIsAdult(true)
            ->setNumber(2)
            ->setMediaType(DiaryKeeper::MEDIA_TYPE_DIGITAL)
            ->setUser($this->createUser($manager, User::generateNoLoginPlaceholder()))
            ->setHousehold($householdOnboardedOther);
        $diaryKeeper->addActingAsProxyFor($diaryKeeperProxiedNoLogin);

        $manager->persist($diaryKeeperProxiedNoLogin);
        $this->setReference('user:diary-keeper:proxied-no-login', $diaryKeeperProxiedNoLogin->getUser());

        $diaryKeeperTraining = (new DiaryKeeper())
            ->setName('Diary Keeper Interviewer Training')
            ->setIsAdult(true)
            ->setNumber(3)
            ->setMediaType(DiaryKeeper::MEDIA_TYPE_DIGITAL)
            ->setUser($this->createUser($manager,  'diary-keeper-training-test@example.com'))
            ->setHousehold($householdOnboardedOther);
        $manager->persist($diaryKeeperTraining);
        $this->setReference('user:diary-keeper:interviewer-training', $diaryKeeperTraining->getUser());

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
        return [HouseholdFixtures::class, UserFixtures::class];
    }
}