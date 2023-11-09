<?php

namespace App\DataFixtures;

use App\Entity\AreaPeriod;
use App\Entity\DiaryKeeper;
use App\Entity\Embeddable\CostOrNil;
use App\Entity\Embeddable\Distance;
use App\Entity\Household;
use App\Entity\Interviewer;
use App\Entity\Journey\Journey;
use App\Entity\Journey\Method;
use App\Entity\Journey\Stage;
use App\Entity\User;
use App\Entity\Vehicle;
use Brick\Math\BigDecimal;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use function PHPUnit\Framework\assertSame;

abstract class AbstractTestFixtures extends Fixture implements FixtureGroupInterface, DependentFixtureInterface
{
    public function getDependencies(): array
    {
        return [NtsFixtures::class];
    }

    protected ?string $password = null;

    /** @var AreaPeriod[] */
    protected array $areaPeriods = [];

    /** @var Interviewer[] */
    protected array $interviewers = [];

    /** @var Household[] */
    protected array $households = [];

    /** @var Vehicle[][] */
    protected array $vehicles = [];

    /** @var DiaryKeeper[] */
    protected array $diaryKeepers = [];

    /** @var Journey[][] */
    protected array $journeys = [];

    /** @var Stage[] */
    protected array $stages = [];

    /** @var Method[] */
    protected array $methods = [];

    protected function createTestDiary(int $areaSerial, int $householdNo, string $startDate, $dkEmail, $dkName)
    {
        $entities = [];
        $entities[] = $area = $this->areaPeriods[$areaSerial] ?? $this->createAreaPeriod($areaSerial);
        $entities[] = $household = $this->createHousehold($area, $householdNo, $startDate);
        $entities[] = $vehicle = $this->createVehicle($household, 'Blue Fiesta', 'car');
        $entities[] = $dk = $this->createDiaryKeeper($household, 1, $dkEmail, $dkName);
        $entities[] = $j = $this->createJourney($dk, 1, "Home", "Huddersfield", "13:30", "14:52", "To go shopping");
        $entities[] = $this->createPrivateStage($j, 'car', null, $vehicle, '12', 1, 24, 560);
        $entities[] = $this->createSimpleStage($j, 'walk', null, "0.3", 1, 8);
        $entities[] = $this->createPublicStage($j, 'train', null, "45", 1, 40, "7.80", 2, "Standard return");
        return $entities;
    }


    protected function persistAll(ObjectManager $manager, $objects) {
        foreach ($objects as $object) $manager->persist($object);
    }

    protected function createAreaPeriod(int $number): AreaPeriod {
        $area =  (new AreaPeriod())
            ->setArea($number);
        $this->areaPeriods[$number] = $area;
        return $area;
    }

    protected function createInterviewer($name, $email, $serial, ?array $areas = null): Interviewer {
        $interviewer = (new Interviewer())
            ->setName($name)
            ->setSerialId($serial)
            ->setUser((new User())
                ->setUsername($email)
                ->setPlainPassword($this->password)
            );

        foreach ($areas ?? array_keys($this->areaPeriods) as $area) {
            $interviewer->addAreaPeriod($this->areaPeriods[$area]);
        }

        return $interviewer;
    }

    protected function createHousehold(AreaPeriod $areaPeriod, int $addressNumber, string $diaryWeekStartDate): Household {
        $household = (new Household())
            ->setAreaPeriod($areaPeriod)
            ->setAddressNumber($addressNumber)
            ->setHouseholdNumber(1)
            ->setDiaryWeekStartDate(new \DateTime($diaryWeekStartDate));

        $this->households[$household->getSerialNumber()] = $household;
        $this->vehicles[$household->getSerialNumber()] = [];
        return $household;
    }

    protected function createVehicle(Household $household, string $name, string $methodKey): Vehicle {
        $method = $this->getMethodByKey($methodKey);
        assertSame(Method::TYPE_PRIVATE, $method->getType());
        $vehicle = (new Vehicle())
            ->setHousehold($household)
            ->setFriendlyName($name)
            ->setMethod($method);
        $this->vehicles[$household->getSerialNumber()][$name] = $vehicle;

        return $vehicle;
    }

    protected function createDiaryKeeper(Household $household, int $capiNumber, string $email, string $name, bool $isAdult = true): DiaryKeeper {
        $diaryKeeper = (new DiaryKeeper())
            ->setName($name)
            ->setIsAdult($isAdult)
            ->setNumber($capiNumber)
            ->setHousehold($household)
            ->setUser((new User)
                ->setUsername($email)
                ->setPlainPassword($this->password)
            );

        $this->diaryKeepers[$email] = $diaryKeeper;
        return $diaryKeeper;
    }

    protected function createJourney(DiaryKeeper $diaryKeeper, int $day, string $startLocation, string $endLocation, string $startTime, string $endTime, string $purpose): Journey {
        $isStartHome = $startLocation === 'Home';
        $isEndHome = $endLocation === 'Home';
        return (new Journey())
            ->setIsPartial(false)
            ->setPurpose($purpose)
            ->setDiaryDay($diaryKeeper->getDiaryDayByNumber($day))
            ->setIsStartHome($isStartHome)
            ->setStartLocation($isStartHome ? null : $startLocation)
            ->setIsEndHome($isEndHome)
            ->setEndLocation($isEndHome ? null : $endLocation)
            ->setStartTime(new \DateTime("1970-01-01 $startTime"))
            ->setEndTime(new \DateTime("1970-01-01 $endTime"));
    }

    protected function getMethodByKey(string $methodKey): ?Method
    {
        return current(array_filter($this->methods, fn(Method $m) => $m->getDescriptionTranslationKey() === $methodKey));
    }

    protected function createBaseStage(string $methodKey, ?string $methodOther, string $distance, int $adultCount, int $travelTime): Stage {
        /** @var Method $method */
        $method = $this->getMethodByKey($methodKey);

        $distanceTravelled = (new Distance())
            ->setValue(BigDecimal::of($distance))
            ->setUnit(Distance::UNIT_MILES);

        return (new Stage())
            ->setMethod($method)
            ->setMethodOther($method->isOtherRequired() ? $methodOther : null)
            ->setAdultCount($adultCount)
            ->setChildCount(0)
            ->setDistanceTravelled($distanceTravelled)
            ->setTravelTime($travelTime);
    }

    protected function createSimpleStage(Journey $journey, string $methodKey, ?string $methodOther, string $distance, int $adultCount, int $travelTime): Stage {
        $stage = $this->createBaseStage($methodKey, $methodOther, $distance, $adultCount, $travelTime);
        assertSame(Method::TYPE_OTHER, $stage->getMethod()->getType());
        $journey->addStage($stage);
        return $stage;
    }

    protected function createPublicStage(Journey $journey, string $methodKey, ?string $methodOther, string $distance, int $adultCount, int $travelTime, ?string $ticketCost, int $boardingCount, string $ticketType): Stage {
        $stage = $this->createBaseStage($methodKey, $methodOther, $distance, $adultCount, $travelTime);
        assertSame(Method::TYPE_PUBLIC, $stage->getMethod()->getType());
        $stage
            ->setTicketCost((new CostOrNil())->decodeFromSingleValue($ticketCost))
            ->setBoardingCount($boardingCount)
            ->setTicketType($ticketType);
        $journey->addStage($stage);
        return $stage;
    }

    protected function createPrivateStage(Journey $journey, string $methodKey, ?string $methodOther, $vehicle, string $distance, int $adultCount, int $travelTime, ?string $parkingCost = null, bool $isDriver = true): Stage {
        $stage = $this->createBaseStage($methodKey, $methodOther, $distance, $adultCount, $travelTime);
        assertSame(Method::TYPE_PRIVATE, $stage->getMethod()->getType());

        $vehicle instanceof Vehicle
            ? $stage->setVehicle($vehicle)
            : $stage->setVehicleOther($vehicleName);

        if ($journey->getDiaryDay()->getDiaryKeeper()->getIsAdult()) {
            $stage
                ->setIsDriver($isDriver)
                ->setParkingCost((new CostOrNil())->decodeFromSingleValue($parkingCost));
        }
        $journey->addStage($stage);
        return $stage;
    }
}
