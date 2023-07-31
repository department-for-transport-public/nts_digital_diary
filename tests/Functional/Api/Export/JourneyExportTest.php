<?php

namespace App\Tests\Functional\Api\Export;

use App\Entity\Journey\Journey;
use App\Tests\DataFixtures\ApiUserFixtures;
use App\Tests\DataFixtures\TestSpecific\JourneyExportTestFixtures;
use App\Tests\Functional\Api\AbstractApiWebTestCase;

class JourneyExportTest extends AbstractApiWebTestCase
{
    protected const ENDPOINT = '/api/v1/survey-data';

    const OTHER = 'other';
    const PRIVATE = 'private';
    const PUBLIC = 'public';

    protected function setUp(): void
    {
        $this->initialiseClientAndLoadFixtures([ApiUserFixtures::class, JourneyExportTestFixtures::class]);
        parent::setUp();
    }

    public function testJourneyExport() {
        $household = $this->getHousehold('household:onboarded');

        $queryParams = ['householdSerials' => $household->getSerialNumber(null, false, false)];
        $response = $this->makeSignedRequestAndGetResponse(self::ENDPOINT, $queryParams, ['expectedResponseCode' => 200]);

        // Extract the journeys from the response
        $journeys = [];

        foreach($response as $household) {
            foreach($household['diaryKeepers'] as $diaryKeeper) {
                if ($diaryKeeper['name'] === 'Test Diary Keeper (Adult)') {
                    foreach ($diaryKeeper['days'] as $day) {
                        foreach ($day['journeys'] as $journey) {
                            $journeys[] = $journey;
                        }
                    }
                }
            }
        }

        $this->assertCount(2, $journeys);
        $this->checkJourney($journeys[0], 1);
        $this->checkJourney($journeys[1], 2);
    }

    protected function checkJourney(array $journey, int $expectedStageCount): void
    {
        $ulidRegex = '/^[0-7][0-9A-HJKMNP-TV-Z]{25}$/';
        $timeRegex = '/^([01]\d|2[0-3]):[0-5][0-9]$/';

        $this->assertMatchesRegularExpression($ulidRegex, $journey['id']);
        $this->assertMatchesRegularExpression($timeRegex, $journey['startTime']);
        $this->assertMatchesRegularExpression($timeRegex, $journey['endTime']);

        $assertValidLocation = function(string $fieldPrefix, mixed $location, mixed $isHome): void {
            $this->assertIsBool($isHome);

            if ($isHome) {
                $this->assertNull($location, "{$fieldPrefix}Location should be null when {$fieldPrefix}IsHome is true");
            } else {
                $this->assertIsString($location, "{$fieldPrefix}Location should be a string when {$fieldPrefix}IsHome is false");
            }
        };

        $assertValidLocation('start', $journey['startLocation'], $journey['startIsHome']);
        $assertValidLocation('end', $journey['endLocation'], $journey['endIsHome']);

        $this->assertIsString($journey['purpose']);
        $this->assertThat(
            $journey['purposeCode'],
            $this->logicalOr(
                $this->isNull(),
                $this->isType('int')
            )
        );

        if ($journey['purpose'] === Journey::TO_GO_HOME) {
            $this->assertEquals(1, $journey['purposeCode']);
        }

        $this->assertThat(
            $journey['sharedFromId'],
            $this->logicalOr(
                $this->isNull(),
                $this->matchesRegularExpression($ulidRegex),
            )
        );

        $this->assertIsArray($journey['_history']);

        $this->assertIsArray($journey['stages']);
        $this->assertCount($expectedStageCount, $journey['stages']);
    }
}