<?php

namespace Api;

use App\Tests\DataFixtures\ApiFixtures;
use App\Tests\DataFixtures\ApiUserFixtures;
use App\Tests\Functional\Api\AbstractApiWebTestCase;

class AllocationTest extends AbstractApiWebTestCase
{
    protected function setUp(): void
    {
        $this->initialiseClientAndLoadFixtures([ApiUserFixtures::class, ApiFixtures::class]);
        parent::setUp();
    }

    public function testAllocation()
    {
        [$areaPeriod1, $areaPeriod2] = $this->getAreaPeriodFixtures();
        $interviewer = $this->getInterviewerUserBySerialId(101)->getInterviewer();
        self::assertCount(1, $interviewer->getAreaPeriods());
        self::assertEquals($areaPeriod1, $interviewer->getAreaPeriods()->get(0));

        $allocationResponse = $this->makeSignedRequestAndGetResponse(
            "/api/v1/interviewers/{$interviewer->getApiId()}/allocate/{$areaPeriod2->getApiId()}",
            [],
            ['method' => 'POST', 'expectedResponseCode' => 201]
        );
        self::assertEquals([0 => $areaPeriod1->getApiId(), 1 => $areaPeriod2->getApiId()], $allocationResponse['area_periods'] ?? null);

        $interviewer = $this->getInterviewerUserBySerialId(101)->getInterviewer();
        self::assertCount(2, $interviewer->getAreaPeriods());
        self::assertEquals($areaPeriod1, $interviewer->getAreaPeriods()->get(0));
        self::assertEquals($areaPeriod2, $interviewer->getAreaPeriods()->get(1));

    }

    public function testDeAllocation()
    {
        [$areaPeriod1, $areaPeriod2] = $this->getAreaPeriodFixtures();
        $interviewer = $this->getInterviewerUserBySerialId(101)->getInterviewer();
        self::assertCount(1, $interviewer->getAreaPeriods());
        self::assertEquals($areaPeriod1, $interviewer->getAreaPeriods()->get(0));

        $allocationResponse = $this->makeSignedRequestAndGetResponse(
            "/api/v1/interviewers/{$interviewer->getApiId()}/deallocate/{$areaPeriod1->getApiId()}",
            [],
            ['method' => 'POST', 'expectedResponseCode' => 201]
        );
        self::assertEquals([], $allocationResponse['area_periods'] ?? null);

        $interviewer = $this->getInterviewerUserBySerialId(101)->getInterviewer();
        self::assertCount(0, $interviewer->getAreaPeriods());
    }

}