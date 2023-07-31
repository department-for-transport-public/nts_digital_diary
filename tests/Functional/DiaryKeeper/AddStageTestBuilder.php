<?php

namespace App\Tests\Functional\DiaryKeeper;

use App\Entity\Distance;
use App\Entity\Journey\Method;
use App\Entity\Journey\Stage;
use App\Entity\User;
use App\Entity\Vehicle;
use App\Form\TravelDiary\StageWizard\VehicleDataMapper;
use App\Tests\Functional\Wizard\Action\CallbackAction;
use App\Tests\Functional\Wizard\Action\Context;
use App\Tests\Functional\Wizard\Form\FormTestCase;
use App\Tests\Functional\Wizard\Action\PathTestAction;
use App\Tests\Functional\Wizard\Action\FormTestAction;
use Doctrine\ORM\EntityManagerInterface;

class AddStageTestBuilder
{
    public static function privateTests(string $diaryKeeperUsername, int $methodId, bool $isAdult, ?string $vehicleName = null, bool $isVehicleOther = false): array
    {
        return array_merge(
            self::baseFragment($methodId, $isAdult),
            self::privateFragment($vehicleName),
            [
                self::baseDatabaseTestCase($diaryKeeperUsername, $methodId),
                self::privateDatabaseTestCase($diaryKeeperUsername, $methodId, $vehicleName, $isVehicleOther)
            ],
        );
    }

    public static function otherTests(string $diaryKeeperUsername, int $methodId, bool $isAdult): array
    {
        return array_merge(
            self::baseFragment($methodId, $isAdult),
            [
                new PathTestAction(''),
                self::baseDatabaseTestCase($diaryKeeperUsername, $methodId),
            ],
        );
    }

    protected static function baseFragment(int $methodId, bool $isAdult): array
    {
        $baseDetailsData = [
            'details[distanceTravelled][value]' => '25',
            'details[distanceTravelled][unit]' => 'miles',
            'details[travelTime]' => '50',
            'details[companions][adultCount]' => '1',
            'details[companions][childCount]' => '1',
        ];

        return [
            new FormTestAction("/add-stage/method", "method_button_group_continue", [
                new FormTestCase([], ["#method_method"]),
                new FormTestCase(["method[method]" => "5"], ["#method_other-other-private"]), // Other private
                new FormTestCase(["method[method]" => "10"], ["#method_other-other-public"]), // Other public
                new FormTestCase(["method[method]" => "$methodId"]), // Walk
            ]),
            new FormTestAction("/add-stage/details", "details_button_group_continue", [
                new FormTestCase([], [
                    "#details_companions_adultCount",
                ]),
                new FormTestCase(array_merge($baseDetailsData, [
                    'details[distanceTravelled][value]' => '0',
                ]), ['#details_distanceTravelled_value']),
                new FormTestCase(array_merge($baseDetailsData, [
                    'details[travelTime]' => '0',
                ]), ['#details_travelTime']),
                new FormTestCase(array_merge($baseDetailsData, [
                    'details[companions][adultCount]' => '-1',
                ]), ['#details_companions_adultCount']),
                new FormTestCase(array_merge($baseDetailsData, [
                    'details[companions][childCount]' => '-1',
                ]), ['#details_companions_childCount']),
                new FormTestCase(array_merge($baseDetailsData, [
                    'details[companions][adultCount]' => '0',
                    'details[companions][childCount]' => '0',
                ]), [
                    $isAdult ? '#details_companions_adultCount' : '#details_companions_childCount',
                ]),
                new FormTestCase($baseDetailsData),
            ]),
        ];
    }

    private static function privateFragment(?string $vehicleName): array
    {
        return [
            new FormTestAction("/add-stage/vehicle", "vehicle_button_group_continue", [
                new FormTestCase([], ['#vehicle_vehicle']),
                new FormTestCase(['vehicle[vehicle]' => VehicleDataMapper::OTHER_KEY], ['#vehicle_vehicleOther']),
                new FormTestCase(['vehicle[vehicle]' => $vehicleName]),
            ]),
            new FormTestAction("/add-stage/driver-and-parking", "driver_and_parking_button_group_continue", [
                new FormTestCase([], ['#driver_and_parking_isDriver', '#driver_and_parking_parkingCost_hasCost']),
                new FormTestCase([
                    'driver_and_parking[isDriver]' => 'true',
                    'driver_and_parking[parkingCost][hasCost]' => 'true',
                    'driver_and_parking[parkingCost][cost]' => 'abc',
                ], ['#driver_and_parking_parkingCost_cost']),
                new FormTestCase([
                    'driver_and_parking[isDriver]' => 'false',
                    'driver_and_parking[parkingCost][hasCost]' => 'true',
                    'driver_and_parking[parkingCost][cost]' => '-10',
                ], ['#driver_and_parking_parkingCost_cost']),
                new FormTestCase([
                    'driver_and_parking[isDriver]' => 'true',
                    'driver_and_parking[parkingCost][hasCost]' => 'true',
                    'driver_and_parking[parkingCost][cost]' => '3.60',
                ]),
            ]),
            new PathTestAction(''),
        ];
    }

    private static function baseDatabaseTestCase(string $diaryKeeperUsername, int $methodId): CallbackAction
    {
        return new CallbackAction(function (Context $context) use ($diaryKeeperUsername, $methodId) {
            $stage = self::getStage($context->getEntityManager(), $diaryKeeperUsername);

            $testCase = $context->getTestCase();
            $testCase->assertEquals($methodId, $stage->getMethod()->getId());
            $testCase->assertEquals('25', strval($stage->getDistanceTravelled()->getValue()));
            $testCase->assertEquals(Distance::UNIT_MILES, $stage->getDistanceTravelled()->getUnit());
            $testCase->assertEquals(50, $stage->getTravelTime());
            $testCase->assertEquals(1, $stage->getAdultCount());
            $testCase->assertEquals(1, $stage->getChildCount());
        });
    }

    private static function privateDatabaseTestCase(string $diaryKeeperUsername, int $methodId, string $vehicleName, bool $isVehicleOther): CallbackAction
    {
        return new CallbackAction(function (Context $context) use ($diaryKeeperUsername, $methodId, $vehicleName, $isVehicleOther) {
            $stage = self::getStage($context->getEntityManager(), $diaryKeeperUsername);

            $testCase = $context->getTestCase();

            /** @var Method $method */
            $method = $context
                ->getEntityManager()
                ->getRepository(Method::class)
                ->find($methodId);

            if (in_array($method->getDescriptionTranslationKey(), Vehicle::VALID_METHOD_KEYS)) {
                if ($isVehicleOther) {
                    // Either:
                    // a) Is a vehicle directly added via other
                    // b) Is a vehicle chosen from the list that was previously added via other
                    $testCase->assertEquals(null, $stage->getVehicle());
                    $testCase->assertEquals($vehicleName, $stage->getVehicleOther());
                } else {
                    $testCase->assertEquals($vehicleName, $stage->getVehicle()->getFriendlyName());
                    $testCase->assertEquals(null, $stage->getVehicleOther());
                }

                $testCase->assertEquals(true, $stage->getIsDriver());
                $testCase->assertTrue($stage->getParkingCost()->getCost()->isEqualTo('3.60'));
            }
        });
    }

    private static function getStage(EntityManagerInterface $entityManager, string $diaryKeeperUsername): Stage
    {
        $diaryKeeper = $entityManager->getRepository(User::class)->getDiaryKeeperJourneysAndStagesForTests($diaryKeeperUsername);
        $journeys = $diaryKeeper->getDiaryDayByNumber(1)->getJourneys();
        $stages = $journeys[0]->getStages();
        return $stages->last();
    }
}