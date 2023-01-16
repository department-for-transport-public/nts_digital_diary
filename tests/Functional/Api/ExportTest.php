<?php

namespace App\Tests\Functional\Api;

use App\Tests\DataFixtures\ApiFixtures;
use App\Tests\DataFixtures\ApiUserFixtures;
use DateTime;

class ExportTest extends AbstractApiWebTestCase
{
    protected const ENDPOINT = '/api/v1/survey-data';

    protected function setUp(): void
    {
        $this->initialiseClientAndLoadFixtures([ApiUserFixtures::class, ApiFixtures::class]);
        parent::setUp();
    }

    public function startEndDatesData(): array
    {
        $timestamp = (new DateTime('2021-11-22'))->getTimestamp();
        $day = 60 * 60 * 24;
        return [
            "inclusive" => [$timestamp, $timestamp + $day, [4]],
            "before" => [$timestamp - $day, $timestamp, []],
            "after" => [$timestamp + $day, $timestamp + (2 * $day), []],
        ];
    }

    /**
     * @dataProvider startEndDatesData
     */
    public function testStartEndDates($start, $end, array $expectedDiaryKeeperCounts)
    {
        $queryParams = $this->getExportTestQueryParams(null, $start, $end);
        $response = $this->makeSignedRequestAndGetResponse(self::ENDPOINT, $queryParams, ['expectedResponseCode' => 200]);

        self::assertCount(count($expectedDiaryKeeperCounts), $response);
        foreach ($response as $k => $household) {
            self::assertCount($expectedDiaryKeeperCounts[$k], $household['diaryKeepers']);
        }
    }

    public function dataParameterRanges(): array {
        $sevenDays = 7 * 24 * 60 * 60;
        $now = time();
        return [
            'start/end 7 days apart' => [['startTime' => $now - $sevenDays, 'endTime' => $now], 200],
            'start/end more than 7 days apart' => [['startTime' => $now - $sevenDays - 1, 'endTime' => $now], 400],
            'start, no end' => [['startTime' => $now], 400],
            'end, no start' => [['endTime' => $now], 400],
            'no households' => [['householdSerials' => ''], 400],
            '10 households' => [['householdSerials' => '1,2,3,4,5,6,7,8,9,10'], 200],
            'more than 10 households' => [['householdSerials' => '1,2,3,4,5,6,7,8,9,10,11'], 400],
        ];
    }

    /**
     * @dataProvider dataParameterRanges
     */
    public function testParameterRanges(array $queryParams, int $expectedCode) {
        $this->makeSignedRequestAndGetResponse(self::ENDPOINT, $queryParams, ['expectedResponseCode' => $expectedCode]);
    }

}