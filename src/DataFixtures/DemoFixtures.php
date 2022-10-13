<?php

namespace App\DataFixtures;

use App\Entity\AreaPeriod;
use App\Entity\DiaryDay;
use App\Entity\DiaryKeeper;
use App\Entity\Distance;
use App\Entity\Household;
use App\Entity\Interviewer;
use App\Entity\Journey\Journey;
use App\Entity\Journey\Method;
use App\Entity\Journey\Stage;
use App\Entity\User;
use App\Entity\Vehicle;
use App\Features;
use DateInterval;
use DateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker;
use RuntimeException;

class DemoFixtures extends Fixture implements FixtureGroupInterface, DependentFixtureInterface
{
    public static function getGroups(): array
    {
        return ["demo"];
    }

    public function getDependencies(): array
    {
        return [NtsFixtures::class];
    }

    protected Faker\Generator $faker;

    /** @var AreaPeriod[] */
    protected array $areaPeriods = [];

    /** @var Interviewer[] */
    protected array $interviewers = [];

    /** @var Household[] */
    protected array $households = [];

    /** @var Vehicle[] */
    protected array $vehicles = [];

    /** @var DiaryKeeper[] */
    protected array $diaryKeepers = [];

    /** @var Journey[] */
    protected array $journeys = [];

    /** @var Stage[] */
    protected array $stages = [];

    /** @var Method[] */
    protected array $methods = [];

    const PASSWORD = 'password';

    const AREA_PERIOD_COUNT = 4;
    const INTERVIEWER_COUNT = 3;
    const HOUSEHOLD_COUNT = 10;
    const MAX_VEHICLES_PER_HOUSEHOLD = 3;
    const MAX_DIARY_KEEPERS_PER_HOUSEHOLD = 5;
    const MAX_JOURNEYS_PER_DIARY_DAY = 3;
    const MAX_JOURNEY_TIME = "+3 hours";
    const MAX_STAGES_PER_JOURNEY = 3;

    public function __construct()
    {
        $this->faker = Faker\Factory::create();
    }

    public function load(ObjectManager $manager)
    {
        if (!Features::isEnabled(Features::DEMO_FIXTURES)) {
            echo "Skipping demo fixtures - disabled by Features\n";
            return;
        }

        $this->methods = $manager->getRepository(Method::class)->findAll();

        $this->persistAll($manager, $this->areaPeriods = array_map([$this, 'generateAreaPeriod'], range(1, self::AREA_PERIOD_COUNT)));
        $this->persistAll($manager, $this->interviewers = array_map([$this, 'generateInterviewer'], range(1, self::INTERVIEWER_COUNT)));
        $this->persistAll($manager, $this->households = array_map([$this, 'generateHousehold'], range(1, self::HOUSEHOLD_COUNT)));
        $this->persistAll($manager, $this->vehicles);
        $this->persistAll($manager, $this->diaryKeepers);
        $this->persistAll($manager, $this->journeys);
        $this->persistAll($manager, $this->stages);

        $manager->flush();
    }

    protected function persistAll(ObjectManager $manager, $objects) {
        foreach ($objects as $object) $manager->persist($object);
    }

    protected function generateAreaPeriod(): AreaPeriod {
        $twoDigit = fn(string $x) => str_pad($x, 2, '0', STR_PAD_LEFT);
        return (new AreaPeriod())
            ->setArea('2' . $twoDigit($this->faker->numberBetween(1, 12)) . "0" . $twoDigit($this->faker->numberBetween(0, 99)))
            ->populateMonthAndYearFromArea();
    }

    protected function generateInterviewer($number): Interviewer {
        $interviewer = (new Interviewer())
            ->setName($this->faker->name)
            ->setUser((new User())
                ->setUsername("interviewer.{$number}@example.com")
                ->setPlainPassword(self::PASSWORD)
            )
            ->setSerialId($number);

        $numberOfAreaPeriods = self::AREA_PERIOD_COUNT;
        for($i=0; $i<$numberOfAreaPeriods; $i++) {
            $interviewer->addAreaPeriod($this->faker->randomElement($this->areaPeriods));
        }

        return $interviewer;
    }

    protected function generateHousehold($generateVehicles = true, $generateDiaryKeepers = true, ?string $overrideTestGroup = null): Household {
        /** @var AreaPeriod $areaPeriod */
        $areaPeriod = $this->faker->randomElement($this->areaPeriods);
        $areaPeriodStartDate = new DateTime("{$areaPeriod->getYear()}-{$areaPeriod->getMonth()}-1");

        $household = (new Household())
            ->setAreaPeriod($areaPeriod)
            ->setAddressNumber($this->faker->numberBetween(1, 17))
            ->setHouseholdNumber(1)
            ->setDiaryWeekStartDate($this->faker->dateTimeBetween($areaPeriodStartDate, (clone $areaPeriodStartDate)->add(new DateInterval('P1M'))))
            ->setIsOnboardingComplete(true);

        if ($generateVehicles) {
            $diaryKeeperCount = $this->faker->numberBetween(0, self::MAX_VEHICLES_PER_HOUSEHOLD);
            for ($idx = 0; $idx < $diaryKeeperCount; $idx++) {
                $vehicle = $this->generateVehicle($household);
                $household->addVehicle($vehicle);
                $this->vehicles[] = $vehicle;
            }
        }

        if ($generateDiaryKeepers) {
            $diaryKeeperCount = $this->faker->numberBetween(1, self::MAX_DIARY_KEEPERS_PER_HOUSEHOLD);
            for ($idx = 0; $idx < $diaryKeeperCount; $idx++) {
                $diaryKeeper = $this->generateDiaryKeeper($household);
                $household->addDiaryKeeper($diaryKeeper);
                $this->diaryKeepers[] = $diaryKeeper;
            }
        }
        return $household;
    }

    protected function generateVehicle(Household $household): Vehicle {
        $methods = array_filter($this->methods, function(Method $val) {
            return in_array($val->getCode(), [4,5,6]);
        });

        return (new Vehicle())
            ->setRegistrationNumber($this->faker->regexify('[A-Z]{2}[0-9]{2}[A-Z]{3}'))
            ->setHousehold($household)
            ->setFriendlyName($this->faker->colorName)
            ->setMethod($this->faker->randomElement($methods));
    }

    protected function generateDiaryKeeper(Household $household, $generateJourneys = true): DiaryKeeper {
        $dkCount = count($this->diaryKeepers) + 1;
        $diaryKeeper = (new DiaryKeeper())
            ->setName($this->faker->firstName)
            ->setIsAdult($this->faker->boolean)
            ->setNumber(count($household->getDiaryKeepers()) + 1)
            ->setHousehold($household)
            ->setUser((new User)
                ->setUsername("diary.keeper.{$dkCount}@example.com")
                ->setPlainPassword(self::PASSWORD)
            );

        if ($generateJourneys) {
            foreach($diaryKeeper->getDiaryDays() as $diaryDay) {
                $journeyCount = $this->faker->numberBetween(0, self::MAX_JOURNEYS_PER_DIARY_DAY);
                for ($idx = 0; $idx < $journeyCount; $idx++) {
                    $journey = $this->generateJourney($diaryDay, $diaryKeeper->getIsAdult());
                    $diaryDay->addJourney($journey);
                    $this->journeys[] = $journey;
                }
            }
        }
        return $diaryKeeper;
    }

    protected function generateJourney(DiaryDay $diaryDay, bool $isAdult, $generateStages = true): Journey {
        $startHome = $this->faker->boolean(25);
        $endHome = !$startHome && $this->faker->boolean(25);
        $startTime = $this->faker->dateTimeInInterval(new DateTime('T00:00:00'), '+23 hours');
        $journey = (new Journey())
            ->setIsPartial($this->faker->boolean(50))
            ->setDiaryDay($diaryDay)
            ->setIsStartHome($startHome)
            ->setStartLocation($startHome ? null : $this->faker->city)
            ->setIsEndHome($endHome)
            ->setEndLocation($endHome ? null : $this->faker->city)
            ->setStartTime($startTime)
            ->setEndTime($this->faker->dateTimeInInterval($startTime, self::MAX_JOURNEY_TIME));

        if ($this->faker->boolean(43)) {
            $journey
                ->setPurpose(Journey::TO_GO_HOME)
                ->setIsEndHome(true)
                ->setEndLocation(null);
        } else {
            $journey->setPurpose($this->faker->text(100));
        }

        if ($generateStages) {
            $stageCount = $this->faker->numberBetween(1, self::MAX_STAGES_PER_JOURNEY);
            for ($idx = 0; $idx < $stageCount; $idx++) {
                $stage = $this->generateStage($journey, $isAdult);
                $journey->addStage($stage);
                $this->stages[] = $stage;
            }
        }

        return $journey;
    }

    protected function generateStage(Journey $journey, bool $isAdult): Stage {
        switch($this->faker->numberBetween(1, 3)) {
            case 1 : return $this->generateSimpleStage($journey);
            case 2 : return $this->generatePrivateStage($journey);
            case 3 : return $this->generatePublicStage($journey, $isAdult);
        }

        throw new RuntimeException('generateStage() - unreachable code reached!');
    }

    protected function generateSimpleStage(Journey $journey): Stage {
        /** @var Method $method */
        $method = $this->faker->randomElement(array_filter($this->methods, fn(Method $x) => $x->getType() === Method::TYPE_OTHER));

        $distanceTravelled = (new Distance())
            ->setValue($this->faker->numberBetween(5, 20))
            ->setUnit(Distance::UNIT_MILES);

        return (new Stage())
            ->setJourney($journey)
            ->setMethod($method)
            ->setMethodOther(is_null($method->getCode()) ? $this->faker->word() : null)
            ->setAdultCount($this->faker->numberBetween(1, 3))
            ->setChildCount($this->faker->numberBetween(0, 2))
            ->setDistanceTravelled($distanceTravelled)
            ->setTravelTime($this->faker->numberBetween(10, 90));
    }

    protected function generatePublicStage(Journey $journey, bool $isAdult): Stage {
        /** @var Method $method */
        $method = $this->faker->randomElement(array_filter($this->methods, fn(Method $x) => $x->getType() === Method::TYPE_PUBLIC));

        $distanceTravelled = (new Distance())
            ->setValue($this->faker->numberBetween(5, 20))
            ->setUnit(Distance::UNIT_MILES);

        return (new Stage())
            ->setJourney($journey)
            ->setMethod($method)
            ->setMethodOther(is_null($method->getCode()) ? $this->faker->word() : null)
            ->setAdultCount($this->faker->numberBetween(1, 3))
            ->setChildCount($this->faker->numberBetween(0, 2))
            ->setDistanceTravelled($distanceTravelled)
            ->setTravelTime($this->faker->numberBetween(10, 90))
            ->setTicketCost($this->faker->biasedNumberBetween(200, 400))
            ->setBoardingCount($this->faker->biasedNumberBetween(1, 2))
            ->setTicketType($this->faker->text(100));
    }

    protected function generatePrivateStage(Journey $journey): Stage {
        /** @var Vehicle $vehicle */
        $vehicle = $this->faker->boolean(30) ?
            null :
            $this->faker->randomElement($journey->getDiaryDay()->getDiaryKeeper()->getHousehold()->getVehicles());

        /** @var Method $method */
        $method = $vehicle ?
            $vehicle->getMethod() :
            $this->faker->randomElement(array_filter($this->methods, fn(Method $x) => $x->getType() === Method::TYPE_PRIVATE));

        $adult = $journey->getDiaryDay()->getDiaryKeeper()->getIsAdult();

        $distanceTravelled = (new Distance())
            ->setValue($this->faker->numberBetween(5, 20))
            ->setUnit(Distance::UNIT_MILES);

        $stage = (new Stage())
            ->setJourney($journey)
            ->setMethod($method)
            ->setMethodOther(is_null($method->getCode()) ? $this->faker->word() : null)
            ->setAdultCount($this->faker->numberBetween(1, 3))
            ->setChildCount($this->faker->numberBetween($adult ? 0 : 1, 2))
            ->setDistanceTravelled($distanceTravelled)
            ->setVehicle($vehicle)
            ->setVehicleOther($vehicle ? null : $this->faker->firstName . "'s ".$this->faker->colorName." ".$this->faker->monthName)
            ->setTravelTime($this->faker->numberBetween(10, 90));

        if ($adult) {
            $stage
                ->setIsDriver($this->faker->boolean)
                ->setParkingCost($this->faker->boolean ? $this->faker->numberBetween(50, 450) : null);
        }

        return $stage;
    }
}
