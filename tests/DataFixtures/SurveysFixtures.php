<?php

namespace App\Tests\DataFixtures;

use App\DataFixtures\FixtureHelper;
use App\DataFixtures\NtsFixtures;
use App\Entity\AreaPeriod;
use App\Entity\DiaryKeeper;
use App\Entity\Household;
use App\Entity\Journey\Journey;
use App\Entity\Journey\Method;
use App\Entity\User;
use DateTime;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class SurveysFixtures extends AbstractFixture implements DependentFixtureInterface
{
    protected ObjectManager $manager;
    protected string $reference;

    public function load(ObjectManager $manager): void
    {
        $this->manager = $manager;

        $this->generateFixturesFor('now');
        $this->generateFixturesFor('59 days ago');
        $this->generateFixturesFor('61 days ago');
        $this->generateFixturesFor('199 days ago');
        $this->generateFixturesFor('201 days ago');
        $this->generateFixturesFor('300 days ago');

        $this->manager->flush();
    }

    protected function generateFixturesFor(string $dateString): void
    {
        $this->reference = str_replace(' ', '-', $dateString);
        $date = new DateTime($dateString);

        $areaPeriod = $this->createAreaPeriod($date);
        $household = $this->createHousehold($date, $areaPeriod);
        $diaryKeeper = $this->createDiaryKeeper($date, $household);

        $journeys = $this->createJourneys($diaryKeeper);
        $this->createStages($journeys, $household);

        $household
            ->setSubmittedAt($date)
            ->setSubmittedBy('Test user');
    }

    protected function createAreaPeriod(DateTime $date): AreaPeriod
    {
        $year = substr($date->format('Y'), -1);
        $month = $date->format('m');

        $areaPeriod = (new AreaPeriod())
            ->setArea("{$year}{$month}777")
            ->populateMonthAndYearFromArea();

        $this->addReference("surveys:{$this->reference}:area-period", $areaPeriod);
        $this->manager->persist($areaPeriod);

        return $areaPeriod;
    }

    protected function createHousehold(DateTime $date, AreaPeriod $areaPeriod): Household
    {
        $household = (new Household())
            ->setHouseholdNumber(1)
            ->setAddressNumber(1)
            ->setAreaPeriod($areaPeriod)
            ->setDiaryWeekStartDate($date)
            ->setIsOnboardingComplete(true);

        HouseholdFixtures::setCheckLetter($household);

        $this->addReference("surveys:{$this->reference}:household", $household);
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
        return [NtsFixtures::class];
    }
}