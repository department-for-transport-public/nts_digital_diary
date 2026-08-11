<?php

namespace App\Tests\Functional\Api;

use App\Entity\AreaPeriod;
use App\Tests\DataFixtures\ApiFixtures;
use App\Tests\DataFixtures\ApiUserFixtures;

/**
 * A problem was found with the API's HMAC signature: The signature only takes into account the request query string.
 *
 * It's feasible that two requests with the same queryString, but different bodies and/or URLs might be submitted with
 * the same timestamp (which only has a granularity of 1 second). The second request would have the same signature, and
 * therefore would be rejected as unauthorized (signature replay), even though it is a valid signature for the request.
 *
 * Various options were floated for fixing the problem:
 *  a) increase the granularity of the timestamp (doesn't solve the problem so much as make it less likely to happen)
 *  b) include the full URL (not just query params), and request body in the signature. This is what should be used in
 *     the future, but would cause issues for existing consumers of the API
 *  c) provide an unused nonce query parameter - this is what was decided as a current solution (tested below)
 */
class SignatureGranularityTest extends AbstractApiWebTestCase
{
    protected function setUp(): void
    {
        $this->initialiseClientAndLoadFixtures([ApiUserFixtures::class, ApiFixtures::class]);
        parent::setUp();
    }

    public function testGetFail(): void
    {
        $areaPeriod1 = $this->fixtureReferenceRepository->getReference('area-period:1', AreaPeriod::class);
        $areaPeriod2 = $this->fixtureReferenceRepository->getReference('area-period:2', AreaPeriod::class);

        // Auth V1 will fail if the nonce doesn't exist or is the same
        $this->pauseUntilSecondBoundary();
        $this->makeSignedRequestAndGetResponse("/api/v1/area_periods/{$areaPeriod1->getApiId()}", [], ['expectedResponseCode' => 401], addNonce: false, authVersion: 1);
        $this->makeSignedRequestAndGetResponse("/api/v1/area_periods/{$areaPeriod2->getApiId()}", [], ['expectedResponseCode' => 401], addNonce: false, authVersion: 1);

        // Auth V2 will fail with the same request in succession
        $this->pauseUntilSecondBoundary();
        $this->makeSignedRequestAndGetResponse("/api/v1/area_periods/{$areaPeriod1->getApiId()}", [], ['expectedResponseCode' => 200], authVersion: 2);
        $this->makeSignedRequestAndGetResponse("/api/v1/area_periods/{$areaPeriod1->getApiId()}", [], ['expectedResponseCode' => 401], authVersion: 2);
    }

    public function testGetSuccess(): void
    {
        $areaPeriod1 = $this->fixtureReferenceRepository->getReference('area-period:1', AreaPeriod::class);
        $areaPeriod2 = $this->fixtureReferenceRepository->getReference('area-period:2', AreaPeriod::class);

        // Auth V1 requires a nonce to differentiate subsequent requests, as the path isn't part of the signature
        $this->pauseUntilSecondBoundary();
        $this->makeSignedRequestAndGetResponse("/api/v1/area_periods/{$areaPeriod1->getApiId()}", ['_nonce' => 1], ['expectedResponseCode' => 401], authVersion: 1);
        $this->makeSignedRequestAndGetResponse("/api/v1/area_periods/{$areaPeriod2->getApiId()}", ['_nonce' => 2], ['expectedResponseCode' => 401], authVersion: 1);

        // Auth V2 doesn't need a nonce as the path is part of the signature
        $this->pauseUntilSecondBoundary();
        $this->makeSignedRequestAndGetResponse("/api/v1/area_periods/{$areaPeriod1->getApiId()}", ['expectedResponseCode' => 200], authVersion: 2);
        $this->makeSignedRequestAndGetResponse("/api/v1/area_periods/{$areaPeriod2->getApiId()}", ['expectedResponseCode' => 200], authVersion: 2);
    }

    /**
     * Since the test are based on the signature being the same, if the two assertions lie either side of a second
     * boundary (eg. 0.95 / 1.05) then we're not testing the right thing. Also, the testGetFail tests actually fails!
     */
    protected function pauseUntilSecondBoundary(): void
    {
        $timestamp = microtime(true);
        $partialSecond = $timestamp - floor($timestamp);

        // Only need to pause for the second boundary in cases where we're already very close
        if ($partialSecond > 0.9) {
            usleep(intval((1 - $partialSecond) * 1000000));
        }
    }
}