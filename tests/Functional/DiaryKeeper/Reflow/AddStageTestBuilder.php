<?php

namespace App\Tests\Functional\DiaryKeeper\Reflow;

use App\Entity\Embeddable\Distance;
use App\Entity\Journey\Method;
use App\Entity\Journey\Stage;
use App\Entity\User;
use App\Entity\Vehicle;
use App\Tests\Functional\Wizard\Action\CallbackAction;
use App\Tests\Functional\Wizard\Action\Context;
use App\Tests\Functional\Wizard\Action\FormTestAction;
use App\Tests\Functional\Wizard\Action\PathTestAction;
use App\Tests\Functional\Wizard\Form\FormTestCase;
use Doctrine\ORM\EntityManagerInterface;

class AddStageTestBuilder
{
    public static function privateTests(string $diaryKeeperUsername, int $methodId, bool $isAdult, ?string $vehicleName = null, bool $isVehicleOther = false): array
    {
        return array_merge(
            self::baseFragment($methodId, $isAdult),
            self::privateFragment($vehicleName),
            self::endFragment(),
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
            self::endFragment(),
            [
                self::baseDatabaseTestCase($diaryKeeperUsername, $methodId),
            ],
        );
    }

    public static function publicTests(string $diaryKeeperUsername, int $methodId, bool $isAdult): array
    {
        return array_merge(
            self::baseFragment($methodId, $isAdult),
            self::publicFragment(),
            self::endFragment(),
            [
                self::baseDatabaseTestCase($diaryKeeperUsername, $methodId),
                self::publicDatabaseTestCase($diaryKeeperUsername),
            ],
        );
    }

    protected static function endFragment(): array
    {
        return [
            new FormTestAction("/add-stage/standard--stage-add-another", "add_another_button_group_continue", [
                new FormTestCase(["add_another[is_final_stage]" => "true"]),
            ]),
            new CallbackAction(function (Context $context): void {
                $path = parse_url($context->getClient()->getCurrentURL(), PHP_URL_PATH);
                $context->getTestCase()->assertMatchesRegularExpression(
                    '#^/travel-diary/journey/[0-9A-Z]+(\#stage-\d+)?$#',
                    $path
                );
            }),
        ];

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
            new FormTestAction("/add-stage", "method_button_group_continue", [
                new FormTestCase(["method[method]" => "$methodId"]),
            ]),
            new FormTestAction("/add-stage/standard--stage-details", "details_button_group_continue", [
                new FormTestCase($baseDetailsData),
            ]),
        ];
    }

    private static function privateFragment(?string $vehicleName): array
    {
        return [
            new FormTestAction("/add-stage/standard--stage-vehicle", "vehicle_button_group_continue", [
                new FormTestCase(['vehicle[vehicle]' => $vehicleName]),
            ]),
            new FormTestAction("/add-stage/standard--stage-driver-and-parking", "driver_and_parking_button_group_continue", [
                new FormTestCase([
                    'driver_and_parking[isDriver]' => 'true',
                    'driver_and_parking[parkingCost][hasCost]' => 'true',
                    'driver_and_parking[parkingCost][cost]' => '3.60',
                ]),
            ]),
        ];
    }

    private static function publicFragment(): array
    {
        return [
            // TODO: Add validation tests once the validation group setup with inherit_data is clarified
            new FormTestAction("/add-stage/standard--stage-ticket-details", "ticket_details_button_group_continue", [
                new FormTestCase([
                    'ticket_details[ticket][ticketType]' => 'Day return',
                    'ticket_details[ticketCost][ticketCost][hasCost]' => 'true',
                    'ticket_details[ticketCost][ticketCost][cost]' => '4.50',
                    'ticket_details[ticketCost][boardingCount]' => '2',
                ]),
            ]),
        ];
    }

    private static function publicDatabaseTestCase(string $diaryKeeperUsername): CallbackAction
    {
        return new CallbackAction(function (Context $context) use ($diaryKeeperUsername): void {
            $stage = self::getStage($context->getEntityManager(), $diaryKeeperUsername);

            $testCase = $context->getTestCase();
            $testCase->assertEquals('Day return', $stage->getTicketType());
            $testCase->assertTrue($stage->getTicketCost()->getCost()->isEqualTo('4.50'));
            $testCase->assertEquals(2, $stage->getBoardingCount());
        });
    }

    private static function baseDatabaseTestCase(string $diaryKeeperUsername, int $methodId): CallbackAction
    {
        return new CallbackAction(function (Context $context) use ($diaryKeeperUsername, $methodId): void {
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
        return new CallbackAction(function (Context $context) use ($diaryKeeperUsername, $methodId, $vehicleName, $isVehicleOther): void {
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