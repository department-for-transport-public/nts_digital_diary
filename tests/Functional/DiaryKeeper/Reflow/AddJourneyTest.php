<?php

namespace App\Tests\Functional\DiaryKeeper\Reflow;

use App\Entity\User;
use App\Tests\DataFixtures\Reflow\StageFixtures;
use App\Tests\Functional\AbstractWizardTest;
use App\Tests\Functional\Wizard\Action\CallbackAction;
use App\Tests\Functional\Wizard\Action\Context;
use App\Tests\Functional\Wizard\Action\FormTestAction;
use App\Tests\Functional\Wizard\Action\MetricsTestAction;
use App\Tests\Functional\Wizard\Action\PathTestAction;
use App\Tests\Functional\Wizard\Form\FormTestCase;

class AddJourneyTest extends AbstractWizardTest
{
    public const int TEST_DAY = 2;
    public const string TEST_USERNAME = 'diary-keeper-adult@example.com';

    public function wizardData(): array
    {
        $baseUrl = "/travel-diary/reflow/day-" . self::TEST_DAY . "/new-add-journey";

        $getData = function (bool $isDestinationHome, int $methodId = 1, bool $isPublicTransport = false) use ($baseUrl): array {
            if ($isDestinationHome) {
                $locations = [
                    "locations[start_choice]" => "other",
                    "locations[startLocation]" => "Testington factory",
                    "locations[end_choice]" => "home",
                ];
            } else {
                $locations = [
                    "locations[start_choice]" => "home",
                    "locations[end_choice]" => "other",
                    "locations[endLocation]" => "Testington factory",
                ];
            }

            $timesData = [
                "journey_details[times][startTime][hour]" => "1",
                "journey_details[times][startTime][minute]" => "59",
                "journey_details[times][startTime][am_or_pm]" => "pm",
                "journey_details[times][endTime][hour]" => "2",
                "journey_details[times][endTime][minute]" => "25",
                "journey_details[times][endTime][am_or_pm]" => "pm",
            ];

            if (!$isDestinationHome) {
                $timesData["journey_details[purpose][purpose]"] = "Eat breakfast";
            }

            $tests = [
                new FormTestAction("{$baseUrl}/wizard-type", "wizard_type_button_group_continue", [
                    new FormTestCase(["wizard_type[wizardType]" => "add"]),
                ]),
                new FormTestAction("{$baseUrl}/standard--locations", "locations_button_group_continue", [
                    new FormTestCase($locations),
                ]),
                new FormTestAction("{$baseUrl}/standard--details", "journey_details_button_group_continue", [
                    new FormTestCase($timesData),
                ]),
                new FormTestAction("{$baseUrl}/standard--stage-method", "method_button_group_continue", [
                    new FormTestCase(["method[method]" => strval($methodId)]),
                ]),
                new FormTestAction("{$baseUrl}/standard--stage-details", "details_button_group_continue", [
                    new FormTestCase([
                        'details[distanceTravelled][value]' => '25',
                        'details[distanceTravelled][unit]' => 'miles',
                        'details[travelTime]' => '50',
                        'details[companions][adultCount]' => '1',
                        'details[companions][childCount]' => '1',
                    ]),
                ]),
                ...($isPublicTransport ? [
                    new FormTestAction("{$baseUrl}/standard--stage-ticket-details", "ticket_details_button_group_continue", [
                        new FormTestCase([
                            'ticket_details[ticket][ticketType]' => 'Day return',
                            'ticket_details[ticketCost][ticketCost][hasCost]' => 'true',
                            'ticket_details[ticketCost][ticketCost][cost]' => '4.50',
                            'ticket_details[ticketCost][boardingCount]' => '2',
                        ]),
                    ]),
                ] : []),
                new FormTestAction("{$baseUrl}/standard--stage-add-another", "add_another_button_group_continue", [
                    new FormTestCase(["add_another[is_final_stage]" => "true"]),
                ]),
                new PathTestAction("#^/travel-diary/journey/[0-9A-Z]+$#", [
                    PathTestAction::OPTION_EXPECTED_PATH_REGEX => true,
                ]),
                new CallbackAction(function (Context $context) use ($isDestinationHome): void {
                    $dk = $context->getEntityManager()->getRepository(User::class)->getDiaryKeeperJourneysAndStagesForTests(self::TEST_USERNAME);
                    $journeys = $dk->getDiaryDayByNumber(self::TEST_DAY)->getJourneys();

                    $testCase = $context->getTestCase();
                    $testCase->assertCount(1, $journeys);
                    $journey = $journeys[0];

                    $expectedPurpose = $isDestinationHome ? 'Go home' : "Eat breakfast";
                    $expectedIsStartHome = !$isDestinationHome;
                    $expectedStartLocation = $isDestinationHome ? 'Testington factory' : null;
                    $expectedIsEndHome = $isDestinationHome;
                    $expectedEndLocation = $isDestinationHome ? null : 'Testington factory';

                    $testCase->assertEquals($expectedPurpose, $journey->getPurpose());
                    $testCase->assertEquals($expectedIsStartHome, $journey->getIsStartHome());
                    $testCase->assertEquals($expectedStartLocation, $journey->getStartLocation());
                    $testCase->assertEquals($expectedIsEndHome, $journey->getIsEndHome());
                    $testCase->assertEquals($expectedEndLocation, $journey->getEndLocation());
                    $testCase->assertTimeEquals(new \DateTime("1:59pm"), $journey->getStartTime());
                    $testCase->assertTimeEquals(new \DateTime("2:25pm"), $journey->getEndTime());

                    $testCase->assertCount(1, $journey->getStages());
                }),
                new MetricsTestAction(['Journey: create' => 1, 'Stage: create' => 1]),
            ];

            return $tests;
        };

        $multiStageData = function () use ($baseUrl): array {
            $tests = [
                new FormTestAction("{$baseUrl}/wizard-type", "wizard_type_button_group_continue", [
                    new FormTestCase(["wizard_type[wizardType]" => "add"]),
                ]),
                new FormTestAction("{$baseUrl}/standard--locations", "locations_button_group_continue", [
                    new FormTestCase([
                        "locations[start_choice]" => "home",
                        "locations[end_choice]" => "other",
                        "locations[endLocation]" => "Testington factory",
                    ]),
                ]),
                new FormTestAction("{$baseUrl}/standard--details", "journey_details_button_group_continue", [
                    new FormTestCase([
                        "journey_details[purpose][purpose]" => "Multi-stage journey",
                        "journey_details[times][startTime][hour]" => "9",
                        "journey_details[times][startTime][minute]" => "00",
                        "journey_details[times][startTime][am_or_pm]" => "am",
                        "journey_details[times][endTime][hour]" => "10",
                        "journey_details[times][endTime][minute]" => "00",
                        "journey_details[times][endTime][am_or_pm]" => "am",
                    ]),
                ]),
                // Stage 1
                new FormTestAction("{$baseUrl}/standard--stage-method", "method_button_group_continue", [
                    new FormTestCase(["method[method]" => "1"]),
                ]),
                new FormTestAction("{$baseUrl}/standard--stage-details", "details_button_group_continue", [
                    new FormTestCase([
                        'details[distanceTravelled][value]' => '10',
                        'details[distanceTravelled][unit]' => 'miles',
                        'details[travelTime]' => '20',
                        'details[companions][adultCount]' => '1',
                        'details[companions][childCount]' => '0',
                    ]),
                ]),
                // Add another = yes
                new FormTestAction("{$baseUrl}/standard--stage-add-another", "add_another_button_group_continue", [
                    new FormTestCase(["add_another[is_final_stage]" => "false"]),
                ]),
                // Stage 2 (loops back to stage-method)
                new FormTestAction("{$baseUrl}/standard--stage-method", "method_button_group_continue", [
                    new FormTestCase(["method[method]" => "1"]),
                ]),
                new FormTestAction("{$baseUrl}/standard--stage-details", "details_button_group_continue", [
                    new FormTestCase([
                        'details[distanceTravelled][value]' => '15',
                        'details[distanceTravelled][unit]' => 'miles',
                        'details[travelTime]' => '30',
                        'details[companions][adultCount]' => '1',
                        'details[companions][childCount]' => '0',
                    ]),
                ]),
                // Add another = no (finish)
                new FormTestAction("{$baseUrl}/standard--stage-add-another", "add_another_button_group_continue", [
                    new FormTestCase(["add_another[is_final_stage]" => "true"]),
                ]),
                new PathTestAction("#^/travel-diary/journey/[0-9A-Z]+$#", [
                    PathTestAction::OPTION_EXPECTED_PATH_REGEX => true,
                ]),
                new CallbackAction(function (Context $context): void {
                    $dk = $context->getEntityManager()->getRepository(User::class)->getDiaryKeeperJourneysAndStagesForTests(self::TEST_USERNAME);
                    $journeys = $dk->getDiaryDayByNumber(self::TEST_DAY)->getJourneys();

                    $testCase = $context->getTestCase();
                    $testCase->assertCount(1, $journeys);
                    $journey = $journeys[0];

                    $testCase->assertEquals('Multi-stage journey', $journey->getPurpose());
                    $testCase->assertCount(2, $journey->getStages());
                    $testCase->assertEquals(20, $journey->getStages()[0]->getTravelTime());
                    $testCase->assertEquals(30, $journey->getStages()[1]->getTravelTime());
                }),
                new MetricsTestAction(['Journey: create' => 1, 'Stage: create' => 2]),
            ];
            return $tests;
        };

        return [
            'Add journey test' => [$getData(false)],
            'Add journey test (destination: home)' => [$getData(true)],
            'Add journey test (public transport)' => [$getData(false, 7, true)],
            'Add journey test (multi-stage)' => [$multiStageData()],
        ];
    }

    /**
     * @dataProvider wizardData
     */
    public function testAddJourneyWizard(array $wizardData): void
    {
        $this->initialiseClientAndLoadFixtures([StageFixtures::class]);
        $this->loginUser(self::TEST_USERNAME);

        $this->client->request('GET', '/travel-diary/reflow/day-' . self::TEST_DAY . '/new-add-journey');

        $this->doWizardTest($wizardData);
    }
}
