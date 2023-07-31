<?php

namespace App\Tests\Functional\Api\Export;

use App\Tests\DataFixtures\ApiUserFixtures;
use App\Tests\DataFixtures\TestSpecific\StageExportTestFixtures;
use App\Tests\Functional\Api\AbstractApiWebTestCase;

class StageExportTest extends AbstractApiWebTestCase
{
    protected const ENDPOINT = '/api/v1/survey-data';

    const OTHER = 'other';
    const PRIVATE = 'private';
    const PUBLIC = 'public';

    protected function setUp(): void
    {
        $this->initialiseClientAndLoadFixtures([ApiUserFixtures::class, StageExportTestFixtures::class]);
        parent::setUp();
    }

    public function testStage() {
        $household = $this->getHousehold('household:onboarded');

        $queryParams = ['householdSerials' => $household->getSerialNumber(null, false, false)];
        $response = $this->makeSignedRequestAndGetResponse(self::ENDPOINT, $queryParams, ['expectedResponseCode' => 200]);

        // Extract the stages from the response
        $stages = [];

        foreach($response as $household) {
            foreach($household['diaryKeepers'] as $diaryKeeper) {
                if ($diaryKeeper['name'] === 'Test Diary Keeper (Adult)') {
                    foreach ($diaryKeeper['days'] as $day) {
                        foreach ($day['journeys'] as $journey) {
                            foreach ($journey['stages'] as $stage) {
                                $stages[] = $stage;
                            }
                        }
                    }
                }
            }
        }

        $this->checkStage($stages[0], 1, self::PUBLIC);
        $this->checkStage($stages[1], 2, self::OTHER);
        $this->checkStage($stages[2], 3, self::PRIVATE);

        // These two are public/private stages with empty costs
        $this->checkStage($stages[3], 4, self::PUBLIC);
        $this->checkStage($stages[4], 5, self::PRIVATE);
    }

    protected function checkStage(array $stage, int $expectedStageNumber, string $type): void
    {
        $isPublic = $type === self::PUBLIC;
        $isPrivate = $type === self::PRIVATE;

        $this->assertIsInt($stage['#']);
        $this->assertGreaterThan(0, $stage['#']);
        $this->assertEquals($expectedStageNumber, $stage['#']);

        $this->assertThat(
            $stage['methodCode'],
            $this->logicalOr($this->isType('int'), $this->isNull()),
        );

        $this->assertThat(
            $stage['methodOther'],
            $this->logicalOr(
                $this->logicalAnd(
                    $this->isType('string'),
                    $this->logicalNot($this->equalTo('')),
                ),
                $this->isNull(),
            ),
        );

        $this->assertFalse($stage['methodCode'] === null && $stage['methodOther'] === null, 'At least one of methodCode or methodOther should be set');

        $this->assertIsString($stage['distance']);
        $this->assertMatchesRegularExpression('/^\d{1,8}+\.\d{2}$/', $stage['distance']);

        $this->assertContains($stage['distanceUnit'], ['meters', 'miles']);

        $this->assertIsInt($stage['childCount']);
        $this->assertGreaterThanOrEqual(0, $stage['childCount']);

        $this->assertIsInt($stage['adultCount']);
        $this->assertGreaterThanOrEqual(0, $stage['adultCount']);

        $this->assertIsInt($stage['travelTime']);
        $this->assertGreaterThan(0, $stage['travelTime']);

        if ($isPublic) {
            $this->assertIsInt($stage['boardingCount']);
            $this->assertGreaterThan(0, $stage['boardingCount']);

            $this->assertThat(
                $stage['ticketCost'],
                $this->logicalOr($this->isType('string'), $this->isNull()),
            );
            $this->assertThat(
                $stage['ticketCost'],
                $this->logicalOr(
                    $this->isNull(),
                    $this->matchesRegularExpression('/^\d{1,8}.\d{2}$/')
                )
            );

            $this->assertIsString($stage['ticketType']);
            $this->assertNotEquals('', $stage['ticketType']);
        } else {
            $this->assertNull($stage['boardingCount']);
            $this->assertNull($stage['ticketCost']);
            $this->assertNull($stage['ticketType']);
        }

        if ($isPrivate) {
            $this->assertIsBool($stage['isDriver']);

            $this->assertThat(
                $stage['parkingCost'],
                $this->logicalOr($this->isType('string'), $this->isNull()),
            );
            $this->assertThat(
                $stage['parkingCost'],
                $this->logicalOr(
                    $this->isNull(),
                    $this->matchesRegularExpression('/^\d{1,8}.\d{2}$/')
                )
            );

            $this->assertIsString($stage['vehicle']);
            $this->assertNotEquals('', $stage['vehicle']);

            $this->assertThat(
                $stage['vehicleCapiNumber'],
                $this->logicalOr($this->isType('int'), $this->isNull()),
            );
        } else {
            $this->assertNull($stage['isDriver']);
            $this->assertNull($stage['parkingCost']);
            $this->assertNull($stage['vehicle']);
            $this->assertNull($stage['vehicleCapiNumber']);
        }

        $this->assertIsArray($stage['_history']);
    }
}