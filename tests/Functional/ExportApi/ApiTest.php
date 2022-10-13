<?php


namespace App\Tests\Functional\ExportApi;


use App\Tests\DataFixtures\ApiFixtures;
use App\Tests\DataFixtures\ExportApiUserFixtures;
use DateTime;

class ApiTest extends AbstractApiWebTestCase
{
    protected function setUp(): void
    {
        $this->initialiseClientAndLoadFixtures([ExportApiUserFixtures::class, ApiFixtures::class]);
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
        $queryString = $this->replacePlaceholders(self::QUERY_STRING, null, $start, $end);
        $this->makeSignedRequest($queryString);
        self::assertResponseStatusCodeSame(200);
        self::assertSame('application/json', $this->client->getResponse()->headers->get('content-type'));
        $response = json_decode($this->client->getResponse()->getContent(), true);
        self::assertCount(count($expectedDiaryKeeperCounts), $response);
        foreach ($response as $k => $household) {
            self::assertCount($expectedDiaryKeeperCounts[$k], $household['diaryKeepers']);
        }
    }
}