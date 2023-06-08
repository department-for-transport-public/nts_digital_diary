<?php

namespace App\Utility\InterviewerTraining;

use App\DataFixtures\FixtureHelper;
use App\Entity\AreaPeriod;
use App\Entity\DiaryKeeper;
use App\Entity\Household;
use App\Entity\Interviewer;
use App\Entity\InterviewerTrainingRecord;
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

    public function checkOrCreateTrainingData(Interviewer $interviewer, bool $flushEntityManager = true): void
    {
        $this->checkOrCreateTrainingAreas($interviewer);

        foreach (InterviewerTrainingRecord::MODULES as $name => $number) {
            if (!$interviewer->hasTrainingRecordsForModule($name)) {
                $this->createTrainingRecordForModule($interviewer, $name, $flushEntityManager);
            }
        }

        if ($flushEntityManager) {
            $this->entityManager->flush();
        }
    }

    public function createTrainingRecordForModule(Interviewer $interviewer, string $module, bool $flushEntityManager = true): void
    {
        $trainingRecord = $this->getTrainingRecord($interviewer, $module);

        switch ($module) {
            case InterviewerTrainingRecord::MODULE_PERSONAL_TRAVEL_DIARY :
                $this->createPersonalDiaryTrainingData($trainingRecord);
                break;

            case InterviewerTrainingRecord::MODULE_ONBOARDING :
                $this->createOnboardingTrainingData($trainingRecord);
                break;

            case InterviewerTrainingRecord::MODULE_DIARY_CORRECTION :
                $this->createExampleForCorrectionTrainingData($trainingRecord);
                break;
        }

        if ($flushEntityManager) {
            $this->entityManager->flush();
        }
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
            $now = new DateTime();
            $ap = (new AreaPeriod())
                ->setArea($serial)
                ->setYear($now->format('Y'))
                ->setMonth($now->format('m'))
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

        $diaryKeeper = $this->createDiaryKeeper('Alice', 1);
        $diaryKeeper2 = $this->createDiaryKeeper('Bob', 2);
        $household = $this->createHousehold($this->getNextAddressNumber($area), 1, new DateTime('1 week ago'), [$diaryKeeper, $diaryKeeper2]);
        $household->setIsJourneySharingEnabled(true);

        $method = $this->entityManager->getRepository(Method::class)->findOneBy(['descriptionTranslationKey' => 'car']);

        $household->addVehicle($vehicle = (new Vehicle())
            ->setPrimaryDriver($diaryKeeper)
            ->setCapiNumber(1)
            ->setFriendlyName('Blue volvo')
            ->setMethod($method)
        );

        $area->addHousehold($household);
        $trainingRecord->setHousehold($household);

        $this->entityManager->persist($diaryKeeper);
        $this->entityManager->persist($diaryKeeper2);
        $this->entityManager->persist($household);
        $this->entityManager->persist($vehicle);

        foreach (Fixtures::getJourneyFixturesForCorrection() as $journeyDef)
        {
            $this->entityManager->persist(
                $journey = FixtureHelper::createJourney($journeyDef, $diaryKeeper->getDiaryDayByNumber($journeyDef->getDayNumber()))
            );
            foreach ($journeyDef->getStageDefinitions() as $stageDef) {
                $this->entityManager->persist(
                    $stage = FixtureHelper::createStage($stageDef, $this->entityManager->getRepository(Method::class), $household->getVehicles()->toArray())
                );
                $journey->addStage($stage);
            }
        }
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