<?php

namespace App\Utility\Screenshots;

use App\Entity\AreaPeriod;
use App\Entity\Interviewer;
use App\Entity\User;
use App\Entity\Vehicle;
use Doctrine\ORM\EntityManagerInterface;

class FixtureManager
{
    const USERNAME = 'screenshots@example.com';
    const PASSWORD = 'password';
    const AREA_CODE = '111111';

    protected EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function createInterviewer(): Interviewer
    {
        $user = (new User())
            ->setUserIdentifier(self::USERNAME)
            ->setPlainPassword(self::PASSWORD);

        $interviewer = (new Interviewer())
            ->setUser($user)
            ->setName('Screenshots Test User')
            ->setSerialId('SCREENSHOT');

        $areaRepository = $this->entityManager->getRepository(AreaPeriod::class);
        $areaPeriod = $areaRepository->findOneBy(['area' => self::AREA_CODE]);

        if (!$areaPeriod) {
            $areaPeriod = (new AreaPeriod())
                ->setArea(self::AREA_CODE)
                ->addInterviewer($interviewer)
                ->populateMonthAndYearFromArea();

            $this->entityManager->persist($areaPeriod);
        }

        $interviewer->addAreaPeriod($areaPeriod);

        $this->entityManager->persist($interviewer);
        $this->entityManager->persist($user);

        $this->entityManager->flush();
        return $interviewer;
    }

    public function deleteExistingInterviewer(): void
    {
        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->findOneBy(['username' => self::USERNAME]);

        if (!$user) {
            return;
        }

        $interviewer = $user->getInterviewer();
        $areas = $interviewer->getAreaPeriods();

        // Clear shared journey flags to prevent constraint problems when deleting journeys
        foreach($areas as $area) {
            foreach($area->getHouseholds() as $household) {
                foreach($household->getDiaryKeepers() as $diaryKeeper) {
                    foreach($diaryKeeper->getDiaryDays() as $day) {
                        foreach($day->getJourneys() as $journey) {
                            $journey->setSharedFrom(null);
                        }
                    }
                }
            }
        }
        $this->entityManager->flush();

        // Now delete everything...
        $this->entityManager->remove($interviewer);
        $this->entityManager->remove($user);

        foreach($areas as $area) {
            $this->entityManager->remove($area);

            foreach($area->getOtpUsers() as $otpUser) {
                $this->entityManager->remove($otpUser);
            }

            foreach($area->getHouseholds() as $household) {
                $this->entityManager->remove($household);
                $household->getVehicles()->forAll(fn($_, Vehicle $vehicle) => $this->entityManager->remove($vehicle));

                foreach($household->getDiaryKeepers() as $diaryKeeper) {
                    $this->entityManager->remove($diaryKeeper);
                    $this->entityManager->remove($diaryKeeper->getUser());

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
        }

        $this->entityManager->flush();

        // TODO: Delete area if (and only if) no interviewers remaining
    }
}