<?php

namespace App\Utility\InterviewerTraining;

use App\DataFixtures\Definition\JourneyDefinition;
use App\DataFixtures\FixtureHelper;
use App\Entity\AreaPeriod;
use App\Entity\DiaryKeeper;
use App\Entity\Distance;
use App\Entity\Household;
use App\Entity\Interviewer;
use App\Entity\InterviewerTrainingRecord;
use App\Entity\Journey\Journey;
use App\Entity\Journey\Method;
use App\Entity\User;
use App\Entity\Vehicle;
use DateTime;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

class InterviewerTrainingHelper
{
    private EntityManagerInterface $entityManager;

    /** @var array<Method>  */
    private array $methods;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function checkOrCreateTrainingData(Interviewer $interviewer): void
    {
        $this->checkOrCreateTrainingAreas($interviewer);

        foreach (InterviewerTrainingRecord::MODULES as $name => $number) {
            if (!$interviewer->hasTrainingRecordsForModule($name)) {
                $this->createTrainingRecordForModule($interviewer, $name);
            }
        }

        $this->entityManager->flush();
    }

    public function createTrainingRecordForModule(Interviewer $interviewer, string $module): void
    {
        // delete existing households for training area modules
        foreach ($interviewer->getTrainingRecordsForModule($module) as $trainingRecord) {
            if ($household = $trainingRecord->getHousehold()) {
                $trainingRecord->getAreaPeriod()->removeHousehold($household);
                $this->entityManager->remove($household);
                $trainingRecord->setHousehold(null);
            }
        }
        $this->entityManager->flush();

        $trainingRecord = $this->getTrainingRecord($interviewer, $module);

        switch ($module) {
            case InterviewerTrainingRecord::MODULE_PERSONAL_TRAVEL_DIARY :
                $this->createPersonalDiaryTrainingData($trainingRecord);
                break;

            case InterviewerTrainingRecord::MODULE_ONBOARDING_PRACTICE :
                $this->createOnboardingTrainingData($trainingRecord);
                break;

            case InterviewerTrainingRecord::MODULE_DIARY_CORRECTION :
                $this->createExampleForCorrectionTrainingData($trainingRecord);
                break;
        }

        $this->entityManager->flush();
    }

    protected function checkOrCreateTrainingAreas(Interviewer $interviewer): void
    {
        $trainingAreas = [
            AreaPeriod::TRAINING_PERSONAL_DIARY_AREA_SERIAL,
            AreaPeriod::TRAINING_ONBOARDING_AREA_SERIAL,
            AreaPeriod::TRAINING_CORRECTION_AREA_SERIAL,
        ];
        foreach ($trainingAreas as $areaSerial)
        {
            $this->checkOrCreateTrainingArea($areaSerial, $interviewer);
        }
    }

    protected function checkOrCreateTrainingArea(string $serial, Interviewer $interviewer): void
    {
        if (!$interviewer->getTrainingAreaPeriodBySerial($serial)) {
            $ap = (new AreaPeriod())
                ->setArea($serial)
                ->setYear(2023)
                ->setMonth(1)
            ;
            $interviewer->addTrainingAreaPeriod($ap);
            $this->entityManager->persist($ap);
        }
    }

    protected function getNextAddressNumber(AreaPeriod $areaPeriod): ?int
    {
        $latestHousehold = array_reduce($areaPeriod->getHouseholds()->toArray(), fn(?Household $a, ?Household $b) => $a?->getAddressNumber() > $b?->getAddressNumber() ? $a : $b);
        if (!$latestHousehold) {
            return 1;
        }

        return ($latestHousehold?->getAddressNumber() ?? 0) + 1;
    }

    protected function getTrainingRecord(Interviewer $interviewer, string $module): InterviewerTrainingRecord
    {
        $trainingRecord = (new InterviewerTrainingRecord())
            ->setInterviewer($interviewer)
            ->setModuleName($module)
            ->setCreatedAt(new DateTimeImmutable());
        $interviewer->addTrainingRecord($trainingRecord);
        $this->entityManager->persist($trainingRecord);
        return $trainingRecord;
    }

    protected function createPersonalDiaryTrainingData(InterviewerTrainingRecord $trainingRecord): void
    {
        $interviewer = $trainingRecord->getInterviewer();
        $area = $interviewer->getTrainingAreaPeriodBySerial(AreaPeriod::TRAINING_PERSONAL_DIARY_AREA_SERIAL);

        $dk = $this->createDiaryKeeper($interviewer->getName(), 1);
        $household = $this->createHousehold($this->getNextAddressNumber($area), 1, new DateTime(), [$dk]);

        $area->addHousehold($household);
        $trainingRecord->setHousehold($household);

        $this->entityManager->persist($dk);
        $this->entityManager->persist($household);
    }

    protected function createOnboardingTrainingData(InterviewerTrainingRecord $trainingRecord): void
    {
        $interviewer = $trainingRecord->getInterviewer();
        $area = $interviewer->getTrainingAreaPeriodBySerial(AreaPeriod::TRAINING_ONBOARDING_AREA_SERIAL);
    }

    protected function createExampleForCorrectionTrainingData(InterviewerTrainingRecord $trainingRecord): void
    {
        $interviewer = $trainingRecord->getInterviewer();
        $area = $interviewer->getTrainingAreaPeriodBySerial(AreaPeriod::TRAINING_CORRECTION_AREA_SERIAL);

        $diaryKeeper1 = $this->createDiaryKeeper('Mary', 1);
        $diaryKeeper2 = $this->createDiaryKeeper('John', 2);
        $diaryKeeper2->getDiaryDayByNumber(1)->setDiaryKeeperNotes("I did not do any travel today");
        $household = $this->createHousehold($this->getNextAddressNumber($area), 1, new DateTime('1 week ago'), [$diaryKeeper1, $diaryKeeper2]);
        $household->setIsJourneySharingEnabled(true);

        $method = $this->entityManager->getRepository(Method::class)->findOneBy(['descriptionTranslationKey' => 'car']);

        $household->addVehicle($vehicle1 = (new Vehicle())
            ->setPrimaryDriver($diaryKeeper1)
            ->setCapiNumber(1)
            ->setFriendlyName('Blue volvo')
            ->setMethod($method)
            ->setOdometerUnit(Vehicle::ODOMETER_UNIT_MILES)
            ->setWeekStartOdometerReading(18001)
            ->setWeekEndOdometerReading(18067)
        );
        $household->addVehicle($vehicle2 = (new Vehicle())
            ->setPrimaryDriver($diaryKeeper2)
            ->setCapiNumber(2)
            ->setFriendlyName('White ford')
            ->setMethod($method)
            ->setOdometerUnit(Vehicle::ODOMETER_UNIT_MILES)
            ->setWeekStartOdometerReading(25440)
            ->setWeekEndOdometerReading(25456)
        );

        $area->addHousehold($household);
        $trainingRecord->setHousehold($household);

        $this->entityManager->persist($diaryKeeper1);
        $this->entityManager->persist($diaryKeeper2);
        $this->entityManager->persist($household);
        $this->entityManager->persist($vehicle1);
        $this->entityManager->persist($vehicle2);

        foreach (Fixtures::getFirstDiaryKeeperJourneyFixturesForCorrection() as $journeyDef)
        {
            $this->createAndPersistJourney($journeyDef, $diaryKeeper1);
        }
        foreach (Fixtures::getSecondDiaryKeeperJourneyFixturesForCorrection() as $journeyDef)
        {
            $this->createAndPersistJourney($journeyDef, $diaryKeeper2);
        }
        // take the 1st journey on day 7 for DK2, and "share it" with DK1
        /** @var Journey $sourceJourney */
        $sourceJourney = $diaryKeeper2->getDiaryDayByNumber(7)->getJourneys()->get(0);
        /** @var Journey $targetJourney */
        $targetJourney = $diaryKeeper1->getDiaryDayByNumber(7)->getJourneys()->get(1);
        $sourceJourney->addSharedTo($targetJourney);

        $diaryKeeper1->setDiaryState(DiaryKeeper::STATE_COMPLETED);
        $diaryKeeper2->setDiaryState(DiaryKeeper::STATE_COMPLETED);
    }

    protected function createAndPersistJourney(JourneyDefinition $journeyDef, DiaryKeeper $diaryKeeper): Journey
    {
        $this->entityManager->persist(
            $journey = FixtureHelper::createJourney($journeyDef, $diaryKeeper->getDiaryDayByNumber($journeyDef->getDayNumber()))
        );
        foreach ($journeyDef->getStageDefinitions() as $stageDef) {
            $this->entityManager->persist(
                $stage = FixtureHelper::createStage($stageDef, $this->entityManager->getRepository(Method::class), $diaryKeeper->getHousehold()->getVehicles()->toArray())
            );
            $journey->addStage($stage);
        }
        return $journey;
    }

    protected function createHousehold(int $addressNumber, int $householdNumber, DateTime $diaryWeekStartDate, $diaryKeepers = []): Household
    {
        $household = (new Household())
            ->setAddressNumber($addressNumber)
            ->setHouseholdNumber($householdNumber)
            ->setDiaryWeekStartDate($diaryWeekStartDate)
            ->setIsOnboardingComplete(true);
        foreach ($diaryKeepers as $diaryKeeper) {
            $household->addDiaryKeeper($diaryKeeper);
        }
        return $household;
    }

    protected function createDiaryKeeper(string $name, int $number, bool $isAdult = true): DiaryKeeper
    {
        $user = (new User())->setUserIdentifier(User::generateNoLoginPlaceholder());
        return (new DiaryKeeper())
            ->setUser($user)
            ->setName($name)
            ->setNumber($number)
            ->setMediaType(DiaryKeeper::MEDIA_TYPE_DIGITAL)
            ->setIsAdult($isAdult)
        ;
    }
}