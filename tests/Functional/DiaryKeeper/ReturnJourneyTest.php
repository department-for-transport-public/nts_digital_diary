<?php

namespace App\Tests\Functional\DiaryKeeper;

use App\Entity\User;
use App\Tests\DataFixtures\StageFixtures;
use App\Tests\Functional\Wizard\Action\CallbackAction;
use App\Tests\Functional\Wizard\Action\Context;
use App\Tests\Functional\Wizard\Form\FormTestCase;
use App\Tests\Functional\Wizard\Action\PathTestAction;
use App\Tests\Functional\Wizard\Action\FormTestAction;
use Doctrine\Common\Collections\ArrayCollection;

class ReturnJourneyTest extends AbstractJourneyTest
{
    const TEST_USERNAME = 'diary-keeper-adult@example.com';

    protected function generateTest(bool $overwriteStageDetails, bool $returnToHome): array
    {
        $linkIndex = $returnToHome ? 0 : 1;      // Which journey? - Home to Wobble, or Wobble to Home (from JourneyFixtures)
        $numberOfStages = $returnToHome ? 1 : 2; // The first journey on day 1 has one stage, but the second has two

        $url = fn(string $pathEnd) => '#^\/travel-diary\/journey\/[0-9A-Z]+' . $pathEnd . '$#';
        $options = [PathTestAction::OPTION_EXPECTED_PATH_REGEX => true];

        $tests[] = new FormTestAction(
            $url('\/return-journey\/introduction'),
            'form_button_group_continue',
            [
                new FormTestCase([]),
            ],
            $options
        );

        $tests[] = new FormTestAction(
            $url('\/return-journey\/day'),
            'target_day_button_group_continue',
            [
                new FormTestCase([], ["#target_day_diaryDay"]),
                new FormTestCase(['target_day[diaryDay]' => 'day-3']),
            ],
            $options
        );

        if (!$returnToHome) {
            $tests[] = new FormTestAction(
                $url('\/return-journey\/purpose'),
                'purpose_button_group_continue',
                [
                    new FormTestCase([], ["#purpose_purpose"]),
                    new FormTestCase(['purpose[purpose]' => 'Eat vegetables']),
                ],
                $options
            );
        }

        $tests[] = new FormTestAction(
            $url('\/return-journey\/times'),
            "times_button_group_continue",
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
            ),
            $options
        );

        for($i=1; $i<=$numberOfStages; $i++) {
            $tests[] = new FormTestAction(
                $url('\/return-journey\/stage-details/'.$i),
                "stage_details_button_group_continue",
                // TODO: this is not testing ticketCost on public stages, or parkingCost on private stages
                $this->getStageDetailsTests($overwriteStageDetails, $i),
                $options
            );
        }

        $tests[] = new PathTestAction($url(''), $options);

        $tests[] = new CallbackAction(function (Context $context)
        use ($returnToHome, $linkIndex, $overwriteStageDetails) {
            $dk = $context->getEntityManager()->getRepository(User::class)->getDiaryKeeperJourneysAndStages(self::TEST_USERNAME);

            $dayOneJourneys = $dk->getDiaryDayByNumber(1)->getJourneys();

            $testCase = $context->getTestCase();
            $testCase->assertCount(2, $dayOneJourneys);
            $sourceJourney = $dayOneJourneys[$linkIndex];

            $dayThreeJourneys = $dk->getDiaryDayByNumber(3)->getJourneys();
            $testCase->assertCount(1, $dayThreeJourneys);

            $targetJourney = $dayThreeJourneys[0];

            $expectedPurpose = $returnToHome ? 'Go home' : 'Eat vegetables';
            $expectedIsStartHome = !$returnToHome;
            $expectedStartLocation = $returnToHome ? 'Wobble' : null;
            $expectedIsEndHome = $returnToHome;
            $expectedEndLocation = $returnToHome ? null : 'Wobble';

            $testCase->assertEquals($expectedPurpose, $targetJourney->getPurpose());
            $testCase->assertEquals($expectedIsStartHome, $targetJourney->getIsStartHome());
            $testCase->assertEquals($expectedStartLocation, $targetJourney->getStartLocation());
            $testCase->assertEquals($expectedIsEndHome, $targetJourney->getIsEndHome());
            $testCase->assertEquals($expectedEndLocation, $targetJourney->getEndLocation());
            $testCase->assertTimeEquals(new \DateTime("1:59pm"), $targetJourney->getStartTime());
            $testCase->assertTimeEquals(new \DateTime("2:25pm"), $targetJourney->getEndTime());

            $targetStages = new ArrayCollection(array_reverse($targetJourney->getStages()->toArray()));
            $this->assertStagesAsExpected($testCase, $sourceJourney->getStages(), $targetStages, $overwriteStageDetails, true);
        });

        return [$tests, $linkIndex];
    }

    public function wizardData(): array
    {
        return
            [
                'Return for "X to home"' => $this->generateTest(false, false),
                'Return for "X to home" w/stage details overwrite' => $this->generateTest(true, false),
                'Return for "Home to X"' => $this->generateTest(false, true),
                'Return for "Home to X" w/stage details overwrite' => $this->generateTest(true, true),
            ];
    }

    /**
     * @dataProvider wizardData
     */
    public function testReturnJourneyWizard(array $wizardData, int $journeyViewLinkIndex)
    {
        $this->initialiseClientAndLoadFixtures([StageFixtures::class]);
        $this->loginUser(self::TEST_USERNAME);
        $this->client->request('GET', '/travel-diary/day-1');
        $this->clickLinkContaining('View', $journeyViewLinkIndex);
        $this->clickLinkContaining('Make a return of this journey');

        $this->doWizardTest($wizardData);
    }
}