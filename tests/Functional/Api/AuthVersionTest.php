<?php

namespace Api;

use App\Entity\AreaPeriod;
use App\Tests\DataFixtures\ApiFixtures;
use App\Tests\DataFixtures\ApiUserFixtures;
use App\Tests\Functional\Api\AbstractApiWebTestCase;

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
class AuthVersionTest extends AbstractApiWebTestCase
{
    protected function setUp(): void
    {
        $this->initialiseClientAndLoadFixtures([ApiUserFixtures::class, ApiFixtures::class]);
        parent::setUp();
    }

    public function testAuthVersions(): void
    {
        $areaPeriod1 = $this->fixtureReferenceRepository->getReference('area-period:1', AreaPeriod::class);

        $this->makeSignedRequestAndGetResponse("/api/v1/area_periods/{$areaPeriod1->getApiId()}", [], ['expectedResponseCode' => 401], authVersion: 1);
        self::assertStringContainsString('v1 is no longer supported', $this->client->getResponse()->getContent());

        $this->makeSignedRequestAndGetResponse("/api/v1/area_periods/{$areaPeriod1->getApiId()}", [], ['expectedResponseCode' => 200], authVersion: 2);
    }
}