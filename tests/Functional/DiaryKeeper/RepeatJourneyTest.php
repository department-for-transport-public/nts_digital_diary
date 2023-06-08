<?php

namespace App\Tests\Functional\DiaryKeeper;

use App\Entity\User;
use App\Tests\DataFixtures\StageFixtures;
use App\Tests\Functional\Wizard\Action\CallbackAction;
use App\Tests\Functional\Wizard\Action\Context;
use App\Tests\Functional\Wizard\Form\CallbackFormTestCase;
use App\Tests\Functional\Wizard\Form\FormTestCase;
use App\Tests\Functional\Wizard\Action\PathTestAction;
use App\Tests\Functional\Wizard\Action\FormTestAction;
use Facebook\WebDriver\WebDriverBy;

class RepeatJourneyTest extends AbstractJourneyTest
{
    const TEST_USERNAME = 'diary-keeper-adult@example.com';

    protected function generateTest(bool $overwriteStageDetails, int $sourceJourneyIndex, int $numberOfStages): array
    {
        $url = fn(string $pathEnd) => '/travel-diary/day-3' . $pathEnd;

        $tests[] = new FormTestAction(
            $url('/repeat-journey/full-introduction'),
            'form_button_group_continue',
            [
                new FormTestCase([]),
            ]
        );

        $tests[] = new FormTestAction(
            $url('/repeat-journey/select-source-day'),
            'source_day_button_group_continue',
            [
                new FormTestCase([], ["#source_day_sourceDayId"]),
                new FormTestCase(['source_day[sourceDayId]' => 'day-1']),
            ]
        );

        $tests[] = new FormTestAction(
            $url('/repeat-journey/select-source-journey'),
            'source_journey_button_group_continue',
            [
                new FormTestCase([], ["#source_journey_sourceJourneyId"]),
                new CallbackFormTestCase(
                    function(Context $context) use ($sourceJourneyIndex) {
                        $input = $context->getClient()->findElement(WebDriverBy::xpath('//div[@id="source_journey_sourceJourneyId"]/div['.($sourceJourneyIndex + 1).']/input'));
                        return ['source_journey[sourceJourneyId]' => $input->getAttribute('value')];
                    }
                ),
            ]
        );

//        $tests[] = new FormTestAction(
//            $url('/repeat-journey/select-target-day'),
//            'target_day_button_group_continue',
//            [
//                new FormTestCase([], ["#target_day_diaryDay"]),
//                new FormTestCase(['target_day[diaryDay]' => 'day-3']),
//            ]
//        );

        $tests[] = new FormTestAction(
            $url('/repeat-journey/purpose'),
            'purpose_button_group_continue',
            [
                new FormTestCase(['purpose[purpose]' => ''], ["#purpose_purpose"]),
                new FormTestCase(['purpose[purpose]' => 'Repeat a journey']),
            ]
        );

        $tests[] = new FormTestAction(
            $url('/repeat-journey/adjust-times'),
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
            )
        );

        for($i=1; $i<=$numberOfStages; $i++) {
            $tests[] = new FormTestAction(
                $url('/repeat-journey/adjust-stage-details/'.$i),
                "stage_details_button_group_continue",
                $this->getStageDetailsTests($overwriteStageDetails, $i),
            );
        }

        $tests[] = new PathTestAction('#^\/travel-diary\/journey\/[A-Z0-9]+$#', [
            PathTestAction::OPTION_EXPECTED_PATH_REGEX => true
        ]);

        $tests[] = new CallbackAction(function (Context $context)
        use ($overwriteStageDetails, $sourceJourneyIndex) {
            $dk = $context->getEntityManager()->getRepository(User::class)->getDiaryKeeperJourneysAndStages(self::TEST_USERNAME);

            $dayOneJourneys = $dk->getDiaryDayByNumber(1)->getJourneys();
            $testCase = $context->getTestCase();

            $testCase->assertCount(2, $dayOneJourneys);
            $sourceJourney = $dayOneJourneys[$sourceJourneyIndex];

            $dayThreeJourneys = $dk->getDiaryDayByNumber(3)->getJourneys();
            $testCase->assertCount(1, $dayThreeJourneys);
            $targetJourney = $dayThreeJourneys[0];

            $testCase->assertEquals('Repeat a journey', $targetJourney->getPurpose());
            $testCase->assertEquals($sourceJourney->getIsStartHome(), $targetJourney->getIsStartHome());
            $testCase->assertEquals($sourceJourney->getStartLocation(), $targetJourney->getStartLocation());
            $testCase->assertEquals($sourceJourney->getIsEndHome(), $targetJourney->getIsEndHome());
            $testCase->assertEquals($sourceJourney->getEndLocation(), $targetJourney->getEndLocation());
            $testCase->assertTimeEquals(new \DateTime("1:59pm"), $targetJourney->getStartTime());
            $testCase->assertTimeEquals(new \DateTime("2:25pm"), $targetJourney->getEndTime());

            $this->assertStagesAsExpected($testCase, $sourceJourney->getStages(), $targetJourney->getStages(), $overwriteStageDetails, false);
        });

        return [$tests];
    }

    public function wizardData(): array
    {
        return
            [
                'Repeat ' => $this->generateTest(false, 0, 1),
                'Return w/stage details overwrite' => $this->generateTest(true, 0, 1),
            ];
    }

    /**
     * @dataProvider wizardData
     */
    public function testRepeatJourneyWizard(array $wizardData)
    {
        $this->initialiseClientAndLoadFixtures([StageFixtures::class]);
        $this->loginUser(self::TEST_USERNAME);
        $this->client->request('GET', '/travel-diary/day-3/repeat-journey');

        $this->doWizardTest($wizardData);
    }
}