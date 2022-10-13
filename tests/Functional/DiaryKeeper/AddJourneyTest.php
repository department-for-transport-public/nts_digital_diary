<?php

namespace App\Tests\Functional\DiaryKeeper;

use App\Entity\User;
use App\Tests\DataFixtures\StageFixtures;
use App\Tests\Functional\Wizard\Action\CallbackAction;
use App\Tests\Functional\Wizard\Action\Context;
use App\Tests\Functional\Wizard\Form\FormTestCase;
use App\Tests\Functional\Wizard\Action\PathTestAction;
use App\Tests\Functional\Wizard\Action\FormTestAction;

class AddJourneyTest extends AbstractJourneyTest
{
    const TEST_DAY = 2;
    const TEST_USERNAME = 'diary-keeper-adult@example.com';

    public function wizardData(): array
    {
        $baseUrl = "/travel-diary/day-" . self::TEST_DAY . "/add-journey";

        $getData = function (bool $isDestinationHome) use ($baseUrl): array {
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
                    "locations[endLocation]" => "Testington factory"
                ];
            }

            $tests = [
                new FormTestAction("{$baseUrl}/locations", "locations_button_group_continue", [
                    new FormTestCase([], ["#locations_start_choice", "#locations_end_choice"]),
                    new FormTestCase(["locations[start_choice]" => "home"], ["#locations_end_choice"]),
                    new FormTestCase(["locations[end_choice]" => "home"], ["#locations_start_choice"]),
                    new FormTestCase(["locations[start_choice]" => "other"], ["#locations_startLocation", "#locations_end_choice"]),
                    new FormTestCase(["locations[end_choice]" => "other"], ["#locations_endLocation", "#locations_start_choice"]),
                    new FormTestCase($locations, []),
                ]),
                new FormTestAction("{$baseUrl}/times", "times_button_group_continue",
                    array_merge(
                        [new FormTestCase([], ["#times_startTime", "#times_endTime"])],
                        $this->generateTimeTests("startTime", "endTime"),
                        $this->generateTimeTests("endTime", "startTime"),
                        [
                            new FormTestCase([
                                "times[startTime][hour]" => "1",
                                "times[startTime][minute]" => "59",
                                "times[startTime][am_or_pm]" => "pm",
                                "times[endTime][hour]" => "2",
                                "times[endTime][minute]" => "25",
                                "times[endTime][am_or_pm]" => "pm",
                            ], []),
                        ],
                    )
                )
            ];

            if (!$isDestinationHome) {
                $tests = array_merge($tests, [
                    new FormTestAction("{$baseUrl}/purpose", "purpose_button_group_continue", [
                        new FormTestCase([], ["#purpose_purpose"]),
                        new FormTestCase(["purpose[purpose]" => "Eat breakfast"]),
                    ])
                ]);
            }

            return array_merge($tests, [
                new PathTestAction("#/travel-diary/journey/[0-9A-Z]+/add-stage/intermediary#", [
                    PathTestAction::OPTION_EXPECTED_PATH_REGEX => true,
                ]),
                new CallbackAction(function (Context $context) use ($isDestinationHome) {
                    $dk = $context->getEntityManager()->getRepository(User::class)->getDiaryKeeperJourneysAndStages(self::TEST_USERNAME);
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
                })
            ]);
        };

        return
            [
                'Add journey test' => [$getData(false)],
                'Add journey test (destination: home)' => [$getData(true)],
            ];
    }

    /**
     * @dataProvider wizardData
     */
    public function testAddJourneyWizard($wizardData)
    {
        $this->initialiseClientAndLoadFixtures([StageFixtures::class]);
        $this->loginUser(self::TEST_USERNAME);

        $this->client->request('GET', '/travel-diary/day-2/add-journey');

        $this->doWizardTest($wizardData);
    }
}