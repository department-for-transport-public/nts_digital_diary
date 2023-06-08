<?php

namespace App\Tests\Functional\DiaryKeeper;

use App\Entity\Journey\Stage;
use App\Tests\Functional\AbstractWizardTest;
use App\Tests\Functional\Wizard\Form\FormTestCase;
use Doctrine\Common\Collections\Collection;

abstract class AbstractJourneyTest extends AbstractWizardTest
{
    protected function generateTimeTests(string $testField, string $otherField): array
    {
        $expectedErrorIds = ["#times_$otherField", "#times_$testField"];

        return [
            new FormTestCase([
                "times[$testField][hour]" => "1",
                "times[$testField][minute]" => "1",
            ], $expectedErrorIds),
            new FormTestCase([
                "times[$testField][hour]" => "1",
                "times[$testField][am_or_pm]" => "pm",
            ], $expectedErrorIds),
            new FormTestCase([
                "times[$testField][minute]" => "1",
                "times[$testField][am_or_pm]" => "pm",
            ], $expectedErrorIds),
            new FormTestCase([
                "times[$testField][hour]" => "-1",
                "times[$testField][minute]" => "1",
                "times[$testField][am_or_pm]" => "pm",
            ], $expectedErrorIds),
            new FormTestCase([
                "times[$testField][hour]" => "25",
                "times[$testField][minute]" => "1",
                "times[$testField][am_or_pm]" => "pm",
            ], $expectedErrorIds),
            new FormTestCase([
                "times[$testField][hour]" => "1",
                "times[$testField][minute]" => "-1",
                "times[$testField][am_or_pm]" => "pm",
            ], $expectedErrorIds),
            new FormTestCase([
                "times[$testField][hour]" => "1",
                "times[$testField][minute]" => "60",
                "times[$testField][am_or_pm]" => "pm",
            ], $expectedErrorIds),
        ];
    }

    protected function assertStagesAsExpected(AbstractWizardTest $testCase, Collection $sourceStages, Collection $targetStages, bool $overwriteStageDetails, bool $stageNumbersFlipped): void
    {
        /**
         * @var Stage $sourceStage
         * @var Stage $targetStage
         */
        $numberOfStages = count($targetStages);
        foreach ($targetStages as $i => $targetStage) {
            $expectStageNumber = $stageNumbersFlipped ? ($numberOfStages - $i) : $i + 1;

            $sourceStage = $sourceStages[$i];
            $expectedAdultCount = $overwriteStageDetails ? (20 + $expectStageNumber) : $sourceStage->getAdultCount();
            $expectedChildCount = $overwriteStageDetails ? (10 + $expectStageNumber) : $sourceStage->getChildCount();
            $expectedTravelTime = $overwriteStageDetails ? (30 + $expectStageNumber) : $sourceStage->getTravelTime();

            $testCase->assertEquals($expectedTravelTime, $targetStage->getTravelTime());
            $testCase->assertEquals($expectedAdultCount, $targetStage->getAdultCount());
            $testCase->assertEquals($expectedChildCount, $targetStage->getChildCount());

            $testCase->assertEquals($expectStageNumber, $targetStage->getNumber());
            $testCase->assertEquals($sourceStage->getMethod(), $targetStage->getMethod());
            $testCase->assertEquals($sourceStage->getMethodOther(), $targetStage->getMethodOther());
            $testCase->assertEquals($sourceStage->getVehicle(), $targetStage->getVehicle());
            $testCase->assertEquals($sourceStage->getVehicleOther(), $targetStage->getVehicleOther());
            $testCase->assertEquals($sourceStage->getParkingCost(), $targetStage->getParkingCost());
            $testCase->assertEquals($sourceStage->getIsDriver(), $targetStage->getIsDriver());
            $testCase->assertEquals($sourceStage->getTicketType(), $targetStage->getTicketType());
            $testCase->assertEquals($sourceStage->getDistanceTravelled(), $targetStage->getDistanceTravelled());
        }
    }

    protected function getStageDetailsTests(bool $overwriteStageDetails, int $finalValueIncrement, string $stageType = 'simple'): array
    {
        $additionalTestData = match($stageType) {
            'public' => [
                'stage_details[ticketCost][hasCost]' => 'false',
            ],
            'private' => [
                'stage_details[parkingCost][hasCost]' => 'false',
            ],
            default => []
        };
        $additionalErrors = match($stageType) {
            'public' => [
                50 => '#stage_details_ticketCost_hasCost',
            ],
            'private' => [
                50 => '#stage_details_parkingCost_hasCost',
            ],
            default => []
        };

        if ($overwriteStageDetails) {
            return [
                new FormTestCase([
                    'stage_details[travelTime]' => '',
                    'stage_details[companions][adultCount]' => '',
                    'stage_details[companions][childCount]' => '',
                ], ['#stage_details_companions_adultCount'] + $additionalErrors),
                new FormTestCase([
                        'stage_details[travelTime]' => '-1',
                        'stage_details[companions][adultCount]' => '-1',
                        'stage_details[companions][childCount]' => '-1',
                    ] + $additionalTestData, [
                    '#stage_details_travelTime',
                    '#stage_details_companions_adultCount',
                    '#stage_details_companions_childCount',
                ]),
                new FormTestCase([
                        'stage_details[travelTime]' => '0',
                        'stage_details[companions][adultCount]' => '0',
                        'stage_details[companions][childCount]' => '0',
                    ] + $additionalTestData, [
                    '#stage_details_travelTime',
                    '#stage_details_companions_adultCount',
                ]),
                new FormTestCase([
                        'stage_details[travelTime]' => (30 + $finalValueIncrement),
                        'stage_details[companions][adultCount]' => (20 + $finalValueIncrement),
                        'stage_details[companions][childCount]' => (10 + $finalValueIncrement),
                    ] + $additionalTestData),
            ];
        }

        return [
            new FormTestCase([] + $additionalTestData), // Details are pre-filled on this page...
        ];
    }
}