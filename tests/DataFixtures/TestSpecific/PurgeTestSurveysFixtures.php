<?php

namespace App\Tests\DataFixtures\TestSpecific;

use App\DataFixtures\FixtureHelper;
use App\DataFixtures\NtsFixtures;
use App\Entity\AreaPeriod;
use App\Entity\DiaryKeeper;
use App\Entity\Household;
use App\Entity\Interviewer;
use App\Entity\Journey\Journey;
use App\Entity\Journey\Method;
use App\Entity\User;
use App\Tests\DataFixtures\HouseholdFixtures;
use App\Tests\DataFixtures\JourneyFixtures;
use App\Tests\DataFixtures\UserFixtures;
use DateTime;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class PurgeTestSurveysFixtures extends AbstractFixture implements DependentFixtureInterface
{
    protected ObjectManager $manager;
    protected string $reference;

    public function load(ObjectManager $manager): void
    {
        $this->manager = $manager;

        $this->generateFixturesFor('now');
        $this->generateFixturesFor('59 days ago');
        $this->generateFixturesFor('61 days ago');
        $this->generateFixturesFor('199 days ago', area: '123456');
        $this->generateFixturesFor('201 days ago', area: '123456');
        $this->generateFixturesFor('300 days ago', includeTraining: true);

        $this->manager->flush();
    }

    /**
     * @throws \Exception
     */
    protected function generateFixturesFor(string $dateString, ?string $area = null, bool $includeTraining = false): void
    {
        $this->reference = str_replace(' ', '-', $dateString);
        $date = new \DateTimeImmutable($dateString);

        $areaPeriod = $this->createAreaPeriod(DateTime::createFromImmutable($date), area: $area);
        $household = $this->createHousehold(DateTime::createFromImmutable($date->modify('-1 week')), $areaPeriod);
        $diaryKeeper = $this->createDiaryKeeper(DateTime::createFromImmutable($date), $household);

        $journeys = $this->createJourneys($diaryKeeper);
        $this->createStages($journeys, $household);

        $household
            ->setSubmittedAt(DateTime::createFromImmutable($date))
            ->setSubmittedBy('Test user');

        $this->createHousehold(DateTime::createFromImmutable($date->modify('-1 week')), $areaPeriod, 2);

        if ($includeTraining) {
            $this->createInterviewerTrainingHousehold(DateTime::createFromImmutable($date));
        }
    }

    protected function createInterviewerTrainingHousehold(DateTime $date): void
    {
        $area = $this->createAreaPeriod($date, isInterviewerTraining: true);

        $this->createHousehold($date, $area);
    }

    protected function createAreaPeriod(DateTime $date, ?string $area = null, bool $isInterviewerTraining = false): AreaPeriod
    {
        $year = substr($date->format('Y'), -1);
        $month = $date->format('m');
//        $day = $date->format('d');

        $trainingInterviewer = $isInterviewerTraining ?
            $this->referenceRepository->getReference('interviewer', Interviewer::class) :
            null;

        $areaPeriod = (new AreaPeriod())
            // normally this would be a specific area for training, but to maintain database constraints, it's not here in this test
            ->setArea($area ?? "{$year}{$month}777")
            ->setMonth($month)
            ->setYear($year)
            ->setTrainingInterviewer($trainingInterviewer)
        ;

        $trainingRef = $isInterviewerTraining ? ':training' : '';
        $this->addReference("surveys:{$this->reference}{$trainingRef}:area-period", $areaPeriod);
        $this->manager->persist($areaPeriod);

        return $areaPeriod;
    }

    protected function createHousehold(DateTime $diaryWeekStartDate, AreaPeriod $areaPeriod, ?int $householdNumber = null): Household
    {
        $household = (new Household())
            ->setHouseholdNumber($householdNumber ?? 1)
            ->setAddressNumber(1)
            ->setAreaPeriod($areaPeriod)
            ->setDiaryWeekStartDate($diaryWeekStartDate)
            ->setIsOnboardingComplete(true);

        HouseholdFixtures::setCheckLetter($household);

        $trainingRef = $areaPeriod->getTrainingInterviewer() ? ':training' : '';
        $householdRef = $householdNumber ? "-{$householdNumber}" : '';
        $this->addReference("surveys:{$this->reference}{$trainingRef}:household{$householdRef}", $household);
        $this->manager->persist($household);

        return $household;
    }

    protected function createDiaryKeeper(DateTime $date, Household $household): DiaryKeeper
    {
        $dateString = $date->format('Ymd');
        $email = "diary-keeper-{$dateString}@example.com";

        $user = (new User())
            ->setUsername($email)
            ->setPlainPassword('password');

        $this->addReference("surveys:{$this->reference}:user", $user);
        $this->manager->persist($user);

        $diaryKeeper = (new DiaryKeeper())
            ->setName('Diary Keeper')
            ->setIsAdult(true)
            ->setNumber(1)
            ->setMediaType(DiaryKeeper::MEDIA_TYPE_DIGITAL)
            ->setUser($user)
            ->setHousehold($household);

        $this->addReference("surveys:{$this->reference}:diary-keeper", $diaryKeeper);
        $this->manager->persist($diaryKeeper);

        return $diaryKeeper;
    }

    /**
     * @return array<string, Journey>
     */
    public function createJourneys(DiaryKeeper $diaryKeeper): array
    {
        $journeys = [];

        foreach (JourneyFixtures::getJourneyDefinitions() as $name => $definition) {
            $day = $diaryKeeper->getDiaryDayByNumber($definition->getDayNumber());
            $journey = FixtureHelper::createJourney($definition, $day);

            $this->addReference("surveys:{$this->reference}:{$name}", $journey);
            $this->manager->persist($journey);

            $journeys[$name] = $journey;
        }

        return $journeys;
    }

    public function createStages(array $journeys, Household $household): array
    {
        $methodRepo = $this->manager->getRepository(Method::class);
        $vehicles = $household->getVehicles()->toArray();

        $stages = [];

        foreach(JourneyFixtures::getJourneyDefinitions() as $journeyName => $journeyDefinition) {
            $journey = $journeys[$journeyName];

            foreach ($journeyDefinition->getStageDefinitions() as $stageName => $stageDefinition) {
                $stage = FixtureHelper::createStage($stageDefinition, $methodRepo, $vehicles);
                $journey->addStage($stage);

                $this->addReference("surveys:{$this->reference}:{$stageName}", $stage);
                $this->manager->persist($stage);

                $stages[$stageName] = $stage;
            }
        }

        return $stages;
    }

    public function getDependencies(): array
    {
        return [NtsFixtures::class, UserFixtures::class];
    }
}
