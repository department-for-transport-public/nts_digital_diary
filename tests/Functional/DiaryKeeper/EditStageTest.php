<?php

namespace App\Tests\Functional\DiaryKeeper;

use App\Entity\Embeddable\Distance;
use App\Entity\Journey\Stage;
use App\Form\TravelDiary\StageWizard\VehicleDataMapper;
use App\Tests\DataFixtures\StageFixtures;
use App\Tests\Functional\AbstractWizardTest;
use App\Tests\Functional\Wizard\Action\CallbackAction;
use App\Tests\Functional\Wizard\Action\Context;
use App\Tests\Functional\Wizard\Action\FormTestAction;
use App\Tests\Functional\Wizard\Action\PathTestAction;
use App\Tests\Functional\Wizard\Form\FormTestCase;

class EditStageTest extends AbstractWizardTest
{
    public const string TEST_USERNAME = 'diary-keeper-adult@example.com';

    private function navigateAndClickChange(Stage $stage, string $changeSuffix): CallbackAction
    {
        return new CallbackAction(function (Context $context) use ($stage, $changeSuffix): void {
            $journeyId = $stage->getJourney()->getId();
            $context->getClient()->request('GET', "/travel-diary/journey/{$journeyId}");
            $context->getTestCase()->clickChangeLinkFor($changeSuffix);
        });
    }

    public function testEditStageDetails(): void
    {
        $this->initialiseClientAndLoadFixtures([StageFixtures::class]);
        $this->loginUser(self::TEST_USERNAME);

        $stage = $this->fixtureReferenceRepository->getReference('journey:1/stage:1', Stage::class);
        $stageId = $stage->getId();
        $journeyId = $stage->getJourney()->getId();

        $this->doWizardTest([
            $this->navigateAndClickChange($stage, 'Time spent travelling'),
            new FormTestAction(
                "/travel-diary/stage/{$stageId}/edit/details",
                'details_button_group_continue',
                [new FormTestCase([
                    'details[distanceTravelled][value]' => '42',
                    'details[distanceTravelled][unit]' => 'metres',
                    'details[travelTime]' => '55',
                    'details[companions][adultCount]' => '2',
                    'details[companions][childCount]' => '3',
                ])]
            ),
            new PathTestAction("#^/travel-diary/journey/{$journeyId}(\#stage-\d+)?$#", [
                PathTestAction::OPTION_EXPECTED_PATH_REGEX => true,
            ]),
            new CallbackAction(function (Context $context) use ($stageId): void {
                $stage = $context->getEntityManager()->getRepository(Stage::class)->find($stageId);
                $testCase = $context->getTestCase();
                $testCase->assertEquals('42', strval($stage->getDistanceTravelled()->getValue()));
                $testCase->assertEquals(Distance::UNIT_METRES, $stage->getDistanceTravelled()->getUnit());
                $testCase->assertEquals(55, $stage->getTravelTime());
                $testCase->assertEquals(2, $stage->getAdultCount());
                $testCase->assertEquals(3, $stage->getChildCount());
            }),
        ]);
    }

    public function testEditVehicle(): void
    {
        $this->initialiseClientAndLoadFixtures([StageFixtures::class]);
        $this->loginUser(self::TEST_USERNAME);

        $stage = $this->fixtureReferenceRepository->getReference('journey:1/stage:1', Stage::class);
        $stageId = $stage->getId();
        $journeyId = $stage->getJourney()->getId();

        // Vehicle edit on a private adult stage is a 2-page wizard:
        // vehicle page (change vehicleOther) → driver-and-parking page (resubmit unchanged) → finish.
        $this->doWizardTest([
            $this->navigateAndClickChange($stage, 'Vehicle'),
            new FormTestAction(
                "/travel-diary/stage/{$stageId}/edit/vehicle",
                'vehicle_button_group_continue',
                [new FormTestCase([
                    'vehicle[vehicle]' => VehicleDataMapper::OTHER_KEY,
                    'vehicle[vehicleOther]' => 'Updated Tesla model X',
                ])]
            ),
            new FormTestAction(
                "/travel-diary/stage/{$stageId}/edit/driver-and-parking",
                'driver_and_parking_button_group_continue',
                [new FormTestCase([])]
            ),
            new PathTestAction("#^/travel-diary/journey/{$journeyId}(\#stage-\d+)?$#", [
                PathTestAction::OPTION_EXPECTED_PATH_REGEX => true,
            ]),
            new CallbackAction(function (Context $context) use ($stageId): void {
                $stage = $context->getEntityManager()->getRepository(Stage::class)->find($stageId);
                $testCase = $context->getTestCase();
                $testCase->assertNull($stage->getVehicle());
                $testCase->assertEquals('Updated Tesla model X', $stage->getVehicleOther());
            }),
        ]);
    }

    public function testEditDriverAndParking(): void
    {
        $this->initialiseClientAndLoadFixtures([StageFixtures::class]);
        $this->loginUser(self::TEST_USERNAME);

        $stage = $this->fixtureReferenceRepository->getReference('journey:1/stage:1', Stage::class);
        $stageId = $stage->getId();
        $journeyId = $stage->getJourney()->getId();

        // Change links for both "driver or passenger" and "parking cost" route to STATE_VEHICLE,
        // which then forces driver-and-parking before finishing. Test the parking-cost row.
        $this->doWizardTest([
            $this->navigateAndClickChange($stage, 'Parking cost'),
            new FormTestAction(
                "/travel-diary/stage/{$stageId}/edit/vehicle",
                'vehicle_button_group_continue',
                [new FormTestCase([])]
            ),
            new FormTestAction(
                "/travel-diary/stage/{$stageId}/edit/driver-and-parking",
                'driver_and_parking_button_group_continue',
                [new FormTestCase([
                    'driver_and_parking[isDriver]' => 'false',
                    'driver_and_parking[parkingCost][hasCost]' => 'true',
                    'driver_and_parking[parkingCost][cost]' => '5.50',
                ])]
            ),
            new PathTestAction("#^/travel-diary/journey/{$journeyId}(\#stage-\d+)?$#", [
                PathTestAction::OPTION_EXPECTED_PATH_REGEX => true,
            ]),
            new CallbackAction(function (Context $context) use ($stageId): void {
                $stage = $context->getEntityManager()->getRepository(Stage::class)->find($stageId);
                $testCase = $context->getTestCase();
                $testCase->assertFalse($stage->getIsDriver());
                $testCase->assertTrue($stage->getParkingCost()->getCost()->isEqualTo('5.50'));
            }),
        ]);
    }

    public function testEditTicketDetails(): void
    {
        $this->initialiseClientAndLoadFixtures([StageFixtures::class]);
        $this->loginUser(self::TEST_USERNAME);

        $stage = $this->fixtureReferenceRepository->getReference('journey:2/stage:1', Stage::class);
        $stageId = $stage->getId();
        $journeyId = $stage->getJourney()->getId();

        // Public-stage edit goes through ticket-type then ticket-cost-and-boardings.
        $this->doWizardTest([
            $this->navigateAndClickChange($stage, 'Ticket cost'),
            new FormTestAction(
                "/travel-diary/stage/{$stageId}/edit/ticket-type",
                'ticket_button_group_continue',
                [new FormTestCase([
                    'ticket[ticketType]' => 'Day return',
                ])]
            ),
            new FormTestAction(
                "/travel-diary/stage/{$stageId}/edit/ticket-cost-and-boardings",
                'ticket_cost_and_boardings_button_group_continue',
                [new FormTestCase([
                    'ticket_cost_and_boardings[ticketCost][hasCost]' => 'true',
                    'ticket_cost_and_boardings[ticketCost][cost]' => '6.20',
                    'ticket_cost_and_boardings[boardingCount]' => '3',
                ])]
            ),
            new PathTestAction("#^/travel-diary/journey/{$journeyId}(\#stage-\d+)?$#", [
                PathTestAction::OPTION_EXPECTED_PATH_REGEX => true,
            ]),
            new CallbackAction(function (Context $context) use ($stageId): void {
                $stage = $context->getEntityManager()->getRepository(Stage::class)->find($stageId);
                $testCase = $context->getTestCase();
                $testCase->assertEquals('Day return', $stage->getTicketType());
                $testCase->assertTrue($stage->getTicketCost()->getCost()->isEqualTo('6.20'));
                $testCase->assertEquals(3, $stage->getBoardingCount());
            }),
        ]);
    }
}
