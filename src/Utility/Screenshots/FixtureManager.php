<?php

namespace App\Utility\Screenshots;

use App\Entity\AreaPeriod;
use App\Entity\DiaryKeeper;
use App\Entity\Household;
use App\Entity\Interviewer;
use App\Entity\Journey\Method;
use App\Entity\User;
use App\Entity\Vehicle;
use App\Utility\DiaryKeeperFeaturesService;
use App\Utility\TravelDiary\SerialHelper;
use Doctrine\ORM\EntityManagerInterface;

class FixtureManager
{
    public const string USERNAME = 'screenshots@example.com';
    public const string PASSWORD = 'password';
    public const string AREA_CODE = '111111';
    public const string REFLOW_AREA_CODE = DiaryKeeperFeaturesService::REFLOW_TEST_AREA;

    public const string ACCOUNT_SETUP_AREA_CODE = '111112';
    public const string ACCOUNT_SETUP_DK_A = 'dk-account-setup-a@example.com';
    public const string ACCOUNT_SETUP_DK_B = 'dk-account-setup-b@example.com';
    public const string ACCOUNT_SETUP_IV_A = 'iv-account-setup-a@example.com';
    public const string ACCOUNT_SETUP_IV_B = 'iv-account-setup-b@example.com';

    public const string REFLOW_DK_1_NAME = 'Alice';
    public const string REFLOW_DK_1_EMAIL = 'alice.reflow@example.com';
    public const string REFLOW_DK_2_NAME = 'Bob';
    public const string REFLOW_DK_3_NAME = 'Charlie';

    public function __construct(protected EntityManagerInterface $entityManager)
    {
    }

    public function createInterviewer(): Interviewer
    {
        $user = (new User())
            ->setUserIdentifier(self::USERNAME)
            ->setPlainPassword(self::PASSWORD)
            ->setHasLoggedIn(true)
        ;

        $interviewer = (new Interviewer())
            ->setUser($user)
            ->setName('Screenshots Test User')
            ->setSerialId('8008');

        foreach ([self::AREA_CODE, self::REFLOW_AREA_CODE] as $areaCode) {
            $areaPeriod = $this->getOrCreateAreaPeriod($areaCode);
            $areaPeriod->addInterviewer($interviewer);
            $interviewer->addAreaPeriod($areaPeriod);
        }

        $this->entityManager->persist($interviewer);
        $this->entityManager->persist($user);

        $this->entityManager->flush();
        return $interviewer;
    }

    protected function getOrCreateAreaPeriod(string $areaCode): AreaPeriod
    {
        $areaRepository = $this->entityManager->getRepository(AreaPeriod::class);
        $areaPeriod = $areaRepository->findOneBy(['area' => $areaCode]);
        if (!$areaPeriod) {
            $now = new \DateTime('first day of this month');
            $areaPeriod = (new AreaPeriod())
                ->setArea($areaCode)
                ->setYear($now->format('Y'))
                ->setMonth($now->format('m'));

            $this->entityManager->persist($areaPeriod);
        }
        return $areaPeriod;
    }

    public function deleteExistingInterviewer(): void
    {
        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->loadUserByIdentifier(self::USERNAME);

        if (!$user) {
            return;
        }

        $interviewer = $user->getInterviewer();
        $areas = $interviewer->getAreaPeriods();

        // Now delete everything...
        foreach ($interviewer->getTrainingAreaPeriods()->toArray() as $area) {
            $this->removeArea($area);
        }

        foreach($areas as $area) {
            $this->removeArea($area);
        }

        $this->entityManager->remove($interviewer);
        $this->entityManager->remove($user);

        $this->entityManager->flush();
    }

    public function createReflowFixtures(): void
    {
        $areaPeriod = $this->getOrCreateAreaPeriod(self::REFLOW_AREA_CODE);

        $household = (new Household())
            ->setHouseholdNumber(1)
            ->setAddressNumber(1)
            ->setAreaPeriod($areaPeriod)
            ->setDiaryWeekStartDate(new \DateTime())
            ->setIsJourneySharingEnabled(true)
        ;
        $this->entityManager->persist($household);

        foreach([
            [1, self::REFLOW_DK_1_NAME, self::REFLOW_DK_1_EMAIL, self::PASSWORD],
            [2, self::REFLOW_DK_2_NAME, null, null],
            [3, self::REFLOW_DK_3_NAME, null, null],
        ] as [$number, $name, $email, $password]) {
            $dk = $this->addDiaryKeeper($household, $number, $name, $email, $password);
            if ($number === 1) {
                $methodRepository = $this->entityManager->getRepository(Method::class);
                $vehicle = (new Vehicle())
                    ->setMethod($methodRepository->findOneBy(['descriptionTranslationKey' => 'car']))
                    ->setFriendlyName('Blue golf')
                    ->setCapiNumber(1)
                    ->setPrimaryDriver($dk);
                $household->addVehicle($vehicle);
                $this->entityManager->persist($vehicle);
            }
        }

        $this->entityManager->flush();
        $household->setIsOnboardingComplete(true);
        $this->entityManager->flush();
    }

    public function createAccountSetupFixtures(): void
    {
        $now = new \DateTime('first day of this month');
        $areaPeriod = (new AreaPeriod())
            ->setArea(self::ACCOUNT_SETUP_AREA_CODE)
            ->setYear($now->format('Y'))
            ->setMonth($now->format('m'));
        $this->entityManager->persist($areaPeriod);

        $household = (new Household())
            ->setHouseholdNumber(1)
            ->setAddressNumber(1)
            ->setAreaPeriod($areaPeriod)
            ->setDiaryWeekStartDate(new \DateTime())
            ->setIsOnboardingComplete(false);
        $household->setCheckLetter(SerialHelper::getCheckLetter(
            $household->getAreaPeriod()->getArea(),
            $household->getAddressNumber(),
            $household->getHouseholdNumber()
        ));
        $this->entityManager->persist($household);

        foreach ([
            [1, 'Account Setup DK A', self::ACCOUNT_SETUP_DK_A],
            [2, 'Account Setup DK B', self::ACCOUNT_SETUP_DK_B],
        ] as [$number, $name, $email]) {
            $this->addDiaryKeeper($household, $number, $name, $email);
        }

        // Flush with onboarding incomplete to avoid AddDiaryKeeperSubscriber sending emails
        $this->entityManager->flush();
        $household->setIsOnboardingComplete(true);

        foreach ([
            ['9001', 'Account Setup IV A', self::ACCOUNT_SETUP_IV_A],
            ['9002', 'Account Setup IV B', self::ACCOUNT_SETUP_IV_B],
        ] as [$serialId, $name, $email]) {
            $user = (new User())
                ->setUserIdentifier($email)
                ->setHasLoggedIn(false);

            $interviewer = (new Interviewer())
                ->setName($name)
                ->setSerialId($serialId)
                ->setUser($user);
            $interviewer->addAreaPeriod($areaPeriod);

            $this->entityManager->persist($user);
            $this->entityManager->persist($interviewer);
        }

        $this->entityManager->flush();
    }

    protected function addDiaryKeeper(Household $household, int $number, string $name, ?string $email = null, ?string $password = null): DiaryKeeper
    {
        $user = (new User())
            ->setUserIdentifier($email ?? User::generateNoLoginPlaceholder())
            ->setHasLoggedIn(!is_null($password));
        if ($password) {
            $user->setPlainPassword($password);
        }

        $dk = (new DiaryKeeper())
            ->setName($name)
            ->setIsAdult(true)
            ->setNumber($number)
            ->setMediaType(DiaryKeeper::MEDIA_TYPE_DIGITAL)
            ->setHousehold($household)
            ->setUser($user);

        $this->entityManager->persist($user);
        $this->entityManager->persist($dk);

        return $dk;
    }

    public function deleteExistingAccountSetupFixtures(): void
    {
        $usernames = [
            self::ACCOUNT_SETUP_DK_A,
            self::ACCOUNT_SETUP_DK_B,
            self::ACCOUNT_SETUP_IV_A,
            self::ACCOUNT_SETUP_IV_B,
        ];

        foreach ($usernames as $username) {
            $this->deleteUser($username);
        }
        $this->entityManager->flush();

        $areaPeriods = $this->entityManager->getRepository(AreaPeriod::class)
            ->findBy(['area' => self::ACCOUNT_SETUP_AREA_CODE]);

        foreach ($areaPeriods as $areaPeriod) {
            foreach ($areaPeriod->getHouseholds() as $household) {
                $this->entityManager->remove($household);
            }
            $this->entityManager->remove($areaPeriod);
        }
        $this->entityManager->flush();
    }

    protected function deleteUser(User|string $user): void
    {
        if (is_string($user)) {
            $userRepository = $this->entityManager->getRepository(User::class);
            $user = $userRepository->loadUserByIdentifier($user);
        }
        if (!$user) return;

        if ($interviewer = $user->getInterviewer()) {
            $this->entityManager->remove($interviewer);
        }
        if ($diaryKeeper = $user->getDiaryKeeper()) {
            $this->entityManager->remove($diaryKeeper);
        }
        $this->entityManager->remove($user);
    }

    protected function removeArea(AreaPeriod $area): void
    {
        $this->entityManager->remove($area);

        foreach($area->getOtpUsers() as $otpUser) {
            $this->entityManager->remove($otpUser);
        }

        foreach($area->getHouseholds() as $household) {
            $this->entityManager->remove($household);
            $household->getVehicles()->forAll(fn($_, Vehicle $vehicle) => $this->entityManager->remove($vehicle));

            foreach($household->getDiaryKeepers() as $diaryKeeper) {
                $this->entityManager->remove($diaryKeeper);
                if ($diaryKeeper->getUser()) $this->entityManager->remove($diaryKeeper->getUser());

                foreach($diaryKeeper->getDiaryDays() as $day) {
                    $this->entityManager->remove($day);

                    foreach($day->getJourneys() as $journey) {
                        $this->entityManager->remove($journey);

                        foreach($journey->getStages() as $stage) {
                            $this->entityManager->remove($stage);
                        }
                    }
                }
            }
        }
        if ($area->getInterviewers()->isEmpty()) {
            $this->entityManager->remove($area);
        }
    }

}