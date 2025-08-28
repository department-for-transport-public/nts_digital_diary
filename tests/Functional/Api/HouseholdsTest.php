<?php

namespace App\Tests\Functional\Api;

use App\Tests\DataFixtures\ApiUserFixtures;
use App\Tests\DataFixtures\TestSpecific\Api\HouseholdersTestFixtures;

class HouseholdsTest extends AbstractApiWebTestCase
{
    protected function setUp(): void
    {
        $this->initialiseClientAndLoadFixtures([ApiUserFixtures::class, HouseholdersTestFixtures::class]);
        parent::setUp();
    }

    protected function getHouseholdEntry(array $response, string $area, int $addressNumber, int $householdNumber): array
    {
        foreach($response as $household) {
            if (
                ($household['area'] ?? null) === $area &&
                ($household['address'] ?? null) === $addressNumber &&
                ($household['household'] ?? null) === $householdNumber
            ) {
                return $household;
            }
        }

        throw new \RuntimeException("No such household $area, $addressNumber, $householdNumber");
    }

    public function testCollectionGet(): void
    {
        $response = $this->makeSignedRequestAndGetResponse("/api/v1/households");

        self::assertCount(2, $response);

        $nonOnboardedHouse = $this->getHouseholdEntry($response, '111984', 6, 1);

        $this->assertEquals([
            'area' => '111984',
            'address' => 6,
            'household' => 1,
            'travelWeekStartDate' => '2021-11-15',
            'householdState' => 'new',
            'diaryCount' => 0,
            'diaryStateCounts' => [
                'new' => 0,
                'in-progress' => 0,
                'completed' => 0,
                'approved' => 0,
                'discarded' => 0
            ]
        ], $nonOnboardedHouse);

        $onboardedHousehouse = $this->getHouseholdEntry($response, '111984', 8, 2);

        $this->assertEquals([
            'area' => '111984',
            'address' => 8,
            'household' => 2,
            'travelWeekStartDate' => '2021-11-22',
            'householdState' => 'in_progress',
            'diaryCount' => 4,
            'diaryStateCounts' => [
                'new' => 1,
                'in-progress' => 1,
                'completed' => 1,
                'approved' => 1,
                'discarded' => 0
            ]
        ], $onboardedHousehouse);
    }
}
