<?php

namespace App\Tests\Functional\Api;

use App\Entity\AreaPeriod;
use App\Entity\OtpUser;
use App\Tests\DataFixtures\ApiFixtures;
use App\Tests\DataFixtures\ApiUserFixtures;
use App\Utility\TravelDiary\AreaPeriodHelper;

class AreasTest extends AbstractApiWebTestCase
{
    protected function setUp(): void
    {
        $this->initialiseClientAndLoadFixtures([ApiUserFixtures::class, ApiFixtures::class]);
        parent::setUp();
    }

    public function testCollectionGet()
    {
        $response = $this->makeSignedRequestAndGetResponse("/api/v1/area_periods");

        $areaPeriodFixtures = $this->getAreaPeriodFixtures();
        self::assertCount(count($areaPeriodFixtures), $response);

        foreach($areaPeriodFixtures as $areaPeriod) {
            $this->assertContainsMatchingAreaPeriod($areaPeriod, $response);
        }
    }
    public function testItemGet()
    {
        $areaPeriod = $this->getAreaPeriodFixture(0);
        $areaPeriodId = $areaPeriod->getApiId();

        $response = $this->makeSignedRequestAndGetResponse("/api/v1/area_periods/{$areaPeriodId}");
        $this->assertMatchesAreaPeriod($areaPeriod, $response);
    }

    public function testItemGetFail()
    {
        $areaPeriod = $this->getAreaPeriodFixture(0);
        $areaPeriodId = $this->garbleId($areaPeriod->getApiId());

        $this->makeSignedRequestAndGetResponse("/api/v1/area_periods/{$areaPeriodId}", [], ['expectedResponseCode' => 404]);
    }


    public function testDeleteSuccess()
    {
        $areaPeriodId = $this->getAreaPeriodFixture(1)->getApiId();

        $this->makeSignedRequestAndGetResponse("/api/v1/area_periods/{$areaPeriodId}", [], ['method' => 'DELETE', 'expectedResponseCode' => 204]);

        // Check it's actually been deleted...
        $areaPeriod = $this->getAreaPeriodById($areaPeriodId);
        $this->assertNull($areaPeriod);
    }

    public function testDeleteFail()
    {
        // This one can't be deleted because it has households
        $areaPeriodId = $this->getAreaPeriodFixture(0)->getApiId();
        $this->makeSignedRequestAndGetResponse("/api/v1/area_periods/{$areaPeriodId}", [], ['method' => 'DELETE', 'expectedResponseCode' => 403]);

        // this one doesn't exist
        $areaPeriodId = $this->garbleId($areaPeriodId);
        $this->makeSignedRequestAndGetResponse("/api/v1/area_periods/{$areaPeriodId}", [], ['method' => 'DELETE', 'expectedResponseCode' => 404]);
    }

    public function dataPostSuccess(): array {
        return [
            [['area' => '211900', 'year' => 2022, 'month' => 11]],
            [['area' => '011900', 'year' => 2030, 'month' => 11]],
            [['area' => '011900', 'year' => 2020, 'month' => 11]],
        ];
    }

    /**
     * @dataProvider dataPostSuccess
     */
    public function testPostSuccess(array $data)
    {
        $this->makeSignedRequestAndGetResponse("/api/v1/area_periods", [], ['method' => 'POST', 'expectedResponseCode' => 201], $data);

        // Check it's actually been created...
        $areaPeriod = $this->getAreaPeriodByArea($data['area']);
        $this->assertMatchesAreaPeriod($areaPeriod, $data, ['check_id' => false]);
    }

    public function dataPostFail(): array
    {
        return [
            // Omitting fields
            [422, ['area' => '211900', 'year' => 2022]],
            [422, ['area' => '211900', 'month' => 11]],
            [422, ['year' => 2022, 'month' => 11]],

            // Empty fields
            [422, ['area' => '', 'year' => 2022, 'month' => 11]],

            // year/month validation issues
            [422, ['area' => '211500', 'year' => 2023, 'month' => 11]],
            [422, ['area' => '211500', 'year' => 2022, 'month' => 10]],
        ];
    }

    /**
     * @dataProvider dataPostFail
     */
    public function testPostFail(int $expectedResponseCode, array $data)
    {
        $this->makeSignedRequestAndGetResponse("/api/v1/area_periods", [], ['method' => 'POST', 'expectedResponseCode' => $expectedResponseCode], $data);
    }

    /**
     * Primarily designed to test the insertion of a large number of areas, simulating what happens at the beginning of
     * each year. In 2023, NatCen is sampling 1164 points, so setting the NTS_BULK_AREA_TEST_COUNT env var to 100
     * `$ AREA_BULK_TEST_COUNT=100 bin/phpunit --filter=testBulkPost`
     * would be a good representation (the test creates that many areas for each month of the year)
     */
    public function testBulkPost()
    {
        $testCount = getenv('NTS_BULK_AREA_TEST_COUNT') ?: 10;

        $initialOtpCount = $this->entityManager->getRepository(OtpUser::class)
            ->createQueryBuilder('ou')
            ->select('count(ou.id) as ouCount')
            ->getQuery()
            ->getSingleScalarResult();

        foreach (range(1, 12) as $month) {
            foreach (range(0, $testCount - 1) as $sample) {
                $this->makeSignedRequestAndGetResponse("/api/v1/area_periods", [], ['method' => 'POST', 'expectedResponseCode' => 201], [
                    'area' => sprintf("3%02d%03d", $month, $sample),
                    'year' => 2023,
                    'month' => $month,
                ]);
            }
        }

        $finalOtpCount = $this->entityManager->getRepository(OtpUser::class)
            ->createQueryBuilder('ou')
            ->select('count(ou.id) as ouCount')
            ->getQuery()
            ->getSingleScalarResult();
        self::assertEquals(
            ($testCount * 12 * AreaPeriodHelper::CODES_PER_AREA) + $initialOtpCount,
            $finalOtpCount
        );
    }

    public function getAreaPeriodById(string $id): ?AreaPeriod
    {
        return $this->entityManager->getRepository(AreaPeriod::class)
            ->findOneBy(['id' => $id]);
    }

    public function getAreaPeriodByArea(string $area): ?AreaPeriod
    {
        return $this->entityManager->getRepository(AreaPeriod::class)
            ->findOneBy(['area' => $area]);
    }

    public function getAreaPeriodFixture(int $index): AreaPeriod
    {
        return $this->getAreaPeriodFixtures()[$index];
    }

    protected function assertContainsMatchingAreaPeriod(AreaPeriod $areaPeriod, array $data, array $options = []): void
    {
        foreach($data as $datum) {
            $area = $datum['area'] ?? null;
            if ($area == $areaPeriod->getArea()) {
                $this->assertMatchesAreaPeriod($areaPeriod, $datum, $options);
                break;
            }
        }
    }

    protected function assertMatchesAreaPeriod(AreaPeriod $areaPeriod, array $data, array $options = []): void
    {
        if ($options['check_id'] ?? true) {
            self::assertEquals($areaPeriod->getId(), $data['id']);
        }

        self::assertEquals($areaPeriod->getYear(), $data['year']);
        self::assertEquals($areaPeriod->getMonth(), $data['month']);
    }
}