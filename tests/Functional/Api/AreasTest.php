<?php

namespace App\Tests\Functional\Api;

use App\Entity\AreaPeriod;
use App\Tests\DataFixtures\ApiFixtures;
use App\Tests\DataFixtures\ApiUserFixtures;

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
    public function testGet()
    {
        $areaPeriod = $this->getAreaPeriodFixture(0);
        $areaPeriodId = $areaPeriod->getId();

        $response = $this->makeSignedRequestAndGetResponse("/api/v1/area_periods/{$areaPeriodId}");
        $this->assertMatchesAreaPeriod($areaPeriod, $response);
    }

    public function testGetFail()
    {
        $areaPeriod = $this->getAreaPeriodFixture(0);
        $areaPeriodId = $this->garbleId($areaPeriod->getId());

        $this->makeSignedRequestAndGetResponse("/api/v1/area_periods/{$areaPeriodId}", [], ['expectedResponseCode' => 404]);
    }

    public function testDeleteSuccess()
    {
        $areaPeriodId = $this->getAreaPeriodFixture(1)->getId();

        $this->makeSignedRequestAndGetResponse("/api/v1/area_periods/{$areaPeriodId}", [], ['method' => 'DELETE', 'expectedResponseCode' => 204]);

        // Check it's actually been deleted...
        $areaPeriod = $this->getAreaPeriodById($areaPeriodId);
        $this->assertNull($areaPeriod);
    }

    public function testDeleteFail()
    {
        // This one can't be deleted because it has households
        $areaPeriodId = $this->getAreaPeriodFixture(0)->getId();
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

    /**
     * @return array<AreaPeriod>
     */
    public function getAreaPeriodFixtures(): array
    {
        return [
            $this->getFixtureByReference('area-period:1'),
            $this->getFixtureByReference('area-period:2'),
        ];
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