<?php

namespace App\Tests\Functional\DiaryKeeper\Reflow;

use App\Entity\User;
use App\Tests\DataFixtures\Reflow\StageFixtures;
use App\Tests\Functional\Wizard\Action\CallbackAction;
use App\Tests\Functional\Wizard\Action\Context;
use Doctrine\Common\Collections\ArrayCollection;
use App\Tests\Functional\Wizard\Action\FormTestAction;
use App\Tests\Functional\Wizard\Action\MetricsTestAction;
use App\Tests\Functional\Wizard\Action\PathTestAction;
use App\Tests\Functional\Wizard\Form\FormTestCase;

class ReturnJourneyTest extends AbstractJourneyTests
{
    public const string TEST_USERNAME = 'diary-keeper-adult@example.com';

    protected function generateTest(bool $returnToHome): array
    {
        $linkIndex = $returnToHome ? 0 : 1;
        $numberOfStages = $returnToHome ? 1 : 2;
        // Stage types are reversed compared to the source journey fixtures
        $stageTypes = $returnToHome ? ['private'] : ['simple', 'public'];

        $url = fn(string $pathEnd): string => '#^\/travel-diary\/reflow\/journey\/[0-9A-Z]+' . $pathEnd . '$#';
        $options = [PathTestAction::OPTION_EXPECTED_PATH_REGEX => true];

        $tests = [];

        $tests[] = new FormTestAction(
            $url('\/return-journey'),
            'target_day_button_group_continue',
            [new FormTestCase(['target_day[diaryDay]' => 'day-3'])],
            $options
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

        if (!$returnToHome) {
            $journeyDetailsData["journey_details[purpose][purpose]"] = "Eat vegetables";
        }

        $tests[] = new FormTestAction(
            $url('\/return-journey\/return--journey-details'),
            'journey_details_button_group_continue',
            [new FormTestCase($journeyDetailsData)],
            $options
        );

        for ($i = 1; $i <= $numberOfStages; $i++) {
            // Accept pre-filled values from source journey, only submitting required cost fields
            $stageData = [];

            $stageType = $stageTypes[$i - 1];
            if ($stageType === 'private') {
                $stageData['stage_details[parkingCost][hasCost]'] = 'false';
            } elseif ($stageType === 'public') {
                $stageData['stage_details[ticketCost][hasCost]'] = 'false';
            }

            $tests[] = new FormTestAction(
                $url('\/return-journey\/return--stage-details\/' . $i),
                'stage_details_button_group_continue',
                [new FormTestCase($stageData)],
                $options
            );
        }

        $tests[] = new PathTestAction('#^\/travel-diary\/journey\/[0-9A-Z]+$#', $options);

        $tests[] = new CallbackAction(
            function (Context $context) use ($returnToHome, $linkIndex): void {
                $dk = $context->getEntityManager()->getRepository(User::class)->getDiaryKeeperJourneysAndStagesForTests(self::TEST_USERNAME);

                $testCase = $context->getTestCase();

                $dayOneJourneys = $dk->getDiaryDayByNumber(1)->getJourneys();
                $testCase->assertCount(2, $dayOneJourneys);
                $sourceJourney = $dayOneJourneys[$linkIndex];

                $dayThreeJourneys = $dk->getDiaryDayByNumber(3)->getJourneys();
                $testCase->assertCount(1, $dayThreeJourneys);

                $targetJourney = $dayThreeJourneys[0];

                // Journey-level assertions
                $expectedPurpose = $returnToHome ? 'Go home' : 'Eat vegetables';
                $testCase->assertEquals($expectedPurpose, $targetJourney->getPurpose());
                $testCase->assertTimeEquals(new \DateTime("1:59pm"), $targetJourney->getStartTime());
                $testCase->assertTimeEquals(new \DateTime("2:25pm"), $targetJourney->getEndTime());

                // Locations should be inverted
                $testCase->assertEquals($sourceJourney->getIsEndHome(), $targetJourney->getIsStartHome());
                $testCase->assertEquals($sourceJourney->getIsStartHome(), $targetJourney->getIsEndHome());
                $testCase->assertEquals($sourceJourney->getEndLocation(), $targetJourney->getStartLocation());
                $testCase->assertEquals($sourceJourney->getStartLocation(), $targetJourney->getEndLocation());

                // Stages should be reversed and contents copied
                $targetStages = new ArrayCollection(array_reverse($targetJourney->getStages()->toArray()));
                $this->assertStagesAsExpected($testCase, $sourceJourney->getStages(), $targetStages, false, true);
            }
        );

        $tests[] = new MetricsTestAction(['Journey: return' => 1]);

        return [$tests, $linkIndex];
    }

    protected function generateDifferentStagesTest(): array
    {
        $linkIndex = 0; // "Home to X" journey
        $url = fn(string $pathEnd): string => '#^\/travel-diary\/reflow\/journey\/[0-9A-Z]+' . $pathEnd . '$#';
        $options = [PathTestAction::OPTION_EXPECTED_PATH_REGEX => true];

        $tests = [];

        $tests[] = new FormTestAction(
            $url('\/return-journey'),
            'target_day_button_group_continue',
            [new FormTestCase(['target_day[diaryDay]' => 'day-3'])],
            $options
        );

        $tests[] = new FormTestAction(
            $url('\/return-journey\/return--journey-details'),
            'journey_details_button_group_continue',
            [new FormTestCase([
                "journey_details[times][startTime][hour]" => "3",
                "journey_details[times][startTime][minute]" => "00",
                "journey_details[times][startTime][am_or_pm]" => "pm",
                "journey_details[times][endTime][hour]" => "4",
                "journey_details[times][endTime][minute]" => "00",
                "journey_details[times][endTime][am_or_pm]" => "pm",
                "journey_details[isSameStages]" => "false",
            ])],
            $options
        );

        // isSameStages=false → clears stages → fresh stage creation via standard-- states
        $tests[] = new FormTestAction(
            $url('\/return-journey\/standard--stage-method'),
            'method_button_group_continue',
            [new FormTestCase(["method[method]" => "1"])], // walk
            $options
        );

        $tests[] = new FormTestAction(
            $url('\/return-journey\/standard--stage-details'),
            'details_button_group_continue',
            [new FormTestCase([
                'details[distanceTravelled][value]' => '10',
                'details[distanceTravelled][unit]' => 'miles',
                'details[travelTime]' => '20',
                'details[companions][adultCount]' => '1',
                'details[companions][childCount]' => '0',
            ])],
            $options
        );

        $tests[] = new FormTestAction(
            $url('\/return-journey\/standard--stage-add-another'),
            'add_another_button_group_continue',
            [new FormTestCase(["add_another[is_final_stage]" => "true"])],
            $options
        );

        $tests[] = new PathTestAction('#^\/travel-diary\/journey\/[0-9A-Z]+$#', $options);

        $tests[] = new CallbackAction(
            function (Context $context): void {
                $dk = $context->getEntityManager()->getRepository(User::class)->getDiaryKeeperJourneysAndStagesForTests(self::TEST_USERNAME);
                $testCase = $context->getTestCase();

                $dayThreeJourneys = $dk->getDiaryDayByNumber(3)->getJourneys();
                $testCase->assertCount(1, $dayThreeJourneys);

                $targetJourney = $dayThreeJourneys[0];

                // Journey has 1 fresh stage (not copied from source)
                $testCase->assertCount(1, $targetJourney->getStages());
                $stage = $targetJourney->getStages()->first();
                $testCase->assertEquals(20, $stage->getTravelTime());
                $testCase->assertEquals(1, $stage->getAdultCount());
            }
        );

        $tests[] = new MetricsTestAction(['Journey: return' => 1]);

        return [$tests, $linkIndex];
    }

    public function wizardData(): array
    {
        return [
            'Return for "Home to X"' => $this->generateTest(true),
            'Return for "X to Home"' => $this->generateTest(false),
            'Return with different stages' => $this->generateDifferentStagesTest(),
        ];
    }

    /**
     * @dataProvider wizardData
     */
    public function testReturnJourneyWizard(array $wizardData, int $journeyViewLinkIndex): void
    {
        $this->initialiseClientAndLoadFixtures([StageFixtures::class]);
        $this->loginUser(self::TEST_USERNAME);
        $this->client->request('GET', '/travel-diary/day-1');
        $this->clickLinkContaining('View', $journeyViewLinkIndex);
        $this->clickLinkContaining('Make a return of this journey');

        $this->doWizardTest($wizardData);
    }
}
