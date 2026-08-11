<?php

namespace App\Tests\Functional\DiaryKeeper\Reflow;

use App\Entity\User;
use App\Tests\DataFixtures\Reflow\StageFixtures;
use App\Tests\Functional\Wizard\Action\CallbackAction;
use App\Tests\Functional\Wizard\Action\Context;
use App\Tests\Functional\Wizard\Action\FormTestAction;
use App\Tests\Functional\Wizard\Action\MetricsTestAction;
use App\Tests\Functional\Wizard\Action\PathTestAction;
use App\Tests\Functional\Wizard\Form\CallbackFormTestCase;
use App\Tests\Functional\Wizard\Form\FormTestCase;
use Facebook\WebDriver\WebDriverBy;

class RepeatJourneyTest extends AbstractJourneyTests
{
    public const TEST_USERNAME = 'diary-keeper-adult@example.com';
    public const TEST_DAY = 3;

    protected function generateTest(int $sourceJourneyIndex, int $numberOfStages, bool $isGoingHome = false): array
    {
        $baseUrl = "/travel-diary/reflow/day-" . self::TEST_DAY . "/new-add-journey";

        $tests = [];

        $tests[] = new FormTestAction(
            "{$baseUrl}/wizard-type",
            'wizard_type_button_group_continue',
            [new FormTestCase(["wizard_type[wizardType]" => "repeat"])]
        );

        $tests[] = new FormTestAction(
            "{$baseUrl}/repeat--source",
            'source_day_journey_button_group_continue',
            [new CallbackFormTestCase(
                function (Context $context) use ($sourceJourneyIndex): array {
                    $input = $context->getClient()->findElement(
                        WebDriverBy::xpath('//div[@id="source_day_journey_day-1-journey"]/div[' . ($sourceJourneyIndex + 1) . ']/input')
                    );
                    return [
                        'source_day_journey[sourceDayNumber]' => 'day-1',
                        'source_day_journey[day-1-journey]' => $input->getAttribute('value'),
                    ];
                }
            )]
        );

        $journeyDetailsData = [
            "journey_details[times][startTime][hour]" => "1",
            "journey_details[times][startTime][minute]" => "59",
            "journey_details[times][startTime][am_or_pm]" => "pm",
            "journey_details[times][endTime][hour]" => "2",
            "journey_details[times][endTime][minute]" => "25",
            "journey_details[times][endTime][am_or_pm]" => "pm",
            "journey_details[isSameStages]" => "true",
        ];

        if (!$isGoingHome) {
            $journeyDetailsData["journey_details[purpose][purpose]"] = "Repeat a journey";
        }

        $tests[] = new FormTestAction(
            "{$baseUrl}/repeat--journey-details",
            'journey_details_button_group_continue',
            [new FormTestCase($journeyDetailsData)]
        );

        for ($i = 1; $i <= $numberOfStages; $i++) {
            // Accept pre-filled values from source journey
            $tests[] = new FormTestAction(
                "{$baseUrl}/repeat--stage-details/{$i}",
                'stage_details_button_group_continue',
                [new FormTestCase([])]
            );
        }

        $tests[] = new PathTestAction('#^\/travel-diary\/journey\/[A-Z0-9]+$#', [
            PathTestAction::OPTION_EXPECTED_PATH_REGEX => true,
        ]);

        $tests[] = new CallbackAction(function (Context $context) use ($sourceJourneyIndex, $isGoingHome): void {
            $dk = $context->getEntityManager()->getRepository(User::class)->getDiaryKeeperJourneysAndStagesForTests(self::TEST_USERNAME);

            $testCase = $context->getTestCase();

            $dayOneJourneys = $dk->getDiaryDayByNumber(1)->getJourneys();
            $testCase->assertCount(2, $dayOneJourneys);

            $dayThreeJourneys = $dk->getDiaryDayByNumber(self::TEST_DAY)->getJourneys();
            $testCase->assertCount(1, $dayThreeJourneys);

            $targetJourney = $dayThreeJourneys[0];
            $sourceJourney = $dayOneJourneys[$sourceJourneyIndex];

            // Journey-level assertions
            $expectedPurpose = $isGoingHome ? $sourceJourney->getPurpose() : 'Repeat a journey';
            $testCase->assertEquals($expectedPurpose, $targetJourney->getPurpose());
            $testCase->assertEquals($sourceJourney->getIsStartHome(), $targetJourney->getIsStartHome());
            $testCase->assertEquals($sourceJourney->getStartLocation(), $targetJourney->getStartLocation());
            $testCase->assertEquals($sourceJourney->getIsEndHome(), $targetJourney->getIsEndHome());
            $testCase->assertEquals($sourceJourney->getEndLocation(), $targetJourney->getEndLocation());
            $testCase->assertTimeEquals(new \DateTime("1:59pm"), $targetJourney->getStartTime());
            $testCase->assertTimeEquals(new \DateTime("2:25pm"), $targetJourney->getEndTime());

            // Stage-level assertions — stages should be copied from source
            $this->assertStagesAsExpected($testCase, $sourceJourney->getStages(), $targetJourney->getStages(), false, false);
        });

        $tests[] = new MetricsTestAction(['Journey: repeat' => 1]);

        return [$tests];
    }

    protected function generateDifferentStagesTest(): array
    {
        $baseUrl = "/travel-diary/reflow/day-" . self::TEST_DAY . "/new-add-journey";

        $tests = [];

        $tests[] = new FormTestAction(
            "{$baseUrl}/wizard-type",
            'wizard_type_button_group_continue',
            [new FormTestCase(["wizard_type[wizardType]" => "repeat"])]
        );

        $tests[] = new FormTestAction(
            "{$baseUrl}/repeat--source",
            'source_day_journey_button_group_continue',
            [new CallbackFormTestCase(
                function (Context $context): array {
                    $input = $context->getClient()->findElement(
                        WebDriverBy::xpath('//div[@id="source_day_journey_day-1-journey"]/div[1]/input')
                    );
                    return [
                        'source_day_journey[sourceDayNumber]' => 'day-1',
                        'source_day_journey[day-1-journey]' => $input->getAttribute('value'),
                    ];
                }
            )]
        );

        $tests[] = new FormTestAction(
            "{$baseUrl}/repeat--journey-details",
            'journey_details_button_group_continue',
            [new FormTestCase([
                "journey_details[purpose][purpose]" => "Different stages repeat",
                "journey_details[times][startTime][hour]" => "3",
                "journey_details[times][startTime][minute]" => "00",
                "journey_details[times][startTime][am_or_pm]" => "pm",
                "journey_details[times][endTime][hour]" => "4",
                "journey_details[times][endTime][minute]" => "00",
                "journey_details[times][endTime][am_or_pm]" => "pm",
                "journey_details[isSameStages]" => "false",
            ])]
        );

        // isSameStages=false → clears stages → fresh stage creation via standard-- states
        $tests[] = new FormTestAction(
            "{$baseUrl}/standard--stage-method",
            'method_button_group_continue',
            [new FormTestCase(["method[method]" => "1"])] // walk
        );

        $tests[] = new FormTestAction(
            "{$baseUrl}/standard--stage-details",
            'details_button_group_continue',
            [new FormTestCase([
                'details[distanceTravelled][value]' => '10',
                'details[distanceTravelled][unit]' => 'miles',
                'details[travelTime]' => '20',
                'details[companions][adultCount]' => '1',
                'details[companions][childCount]' => '0',
            ])]
        );

        $tests[] = new FormTestAction(
            "{$baseUrl}/standard--stage-add-another",
            'add_another_button_group_continue',
            [new FormTestCase(["add_another[is_final_stage]" => "true"])]
        );

        $tests[] = new PathTestAction('#^\/travel-diary\/journey\/[A-Z0-9]+$#', [
            PathTestAction::OPTION_EXPECTED_PATH_REGEX => true,
        ]);

        $tests[] = new CallbackAction(function (Context $context): void {
            $dk = $context->getEntityManager()->getRepository(User::class)->getDiaryKeeperJourneysAndStagesForTests(self::TEST_USERNAME);
            $testCase = $context->getTestCase();

            $dayThreeJourneys = $dk->getDiaryDayByNumber(self::TEST_DAY)->getJourneys();
            $testCase->assertCount(1, $dayThreeJourneys);

            $targetJourney = $dayThreeJourneys[0];
            $testCase->assertEquals('Different stages repeat', $targetJourney->getPurpose());

            // Journey has 1 fresh stage (not copied from source)
            $testCase->assertCount(1, $targetJourney->getStages());
            $stage = $targetJourney->getStages()->first();
            $testCase->assertEquals(20, $stage->getTravelTime());
            $testCase->assertEquals(1, $stage->getAdultCount());
        });

        $tests[] = new MetricsTestAction(['Journey: repeat' => 1]);

        return [$tests];
    }

    public function wizardData(): array
    {
        return [
            'Repeat journey (1 stage)' => $this->generateTest(0, 1),
            'Repeat journey (2 stages, going home)' => $this->generateTest(1, 2, true),
            'Repeat with different stages' => $this->generateDifferentStagesTest(),
        ];
    }

    /**
     * @dataProvider wizardData
     */
    public function testRepeatJourneyWizard(array $wizardData): void
    {
        $this->initialiseClientAndLoadFixtures([StageFixtures::class]);
        $this->loginUser(self::TEST_USERNAME);

        $this->client->request('GET', '/travel-diary/reflow/day-' . self::TEST_DAY . '/new-add-journey');

        $this->doWizardTest($wizardData);
    }
}
