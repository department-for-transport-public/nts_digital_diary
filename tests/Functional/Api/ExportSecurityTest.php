<?php

namespace App\Tests\Functional\Api;

use App\Tests\DataFixtures\ApiUserFixtures;

class ExportSecurityTest extends AbstractApiWebTestCase
{
    protected const ENDPOINT = '/api/v1/survey-data';

    protected function setUp(): void
    {
        $this->initialiseClientAndLoadFixtures([ApiUserFixtures::class]);
        parent::setUp();
    }

    public function testUnsignedRequest()
    {
        $queryString = $this->queryParamsToQueryString($this->getExportTestQueryParams());
        $this->client->request('GET', self::ENDPOINT."?{$queryString}");
        $this->assertResponseStatusCodeSame(401);
    }

    public function testInvalidSecret()
    {
        $apiUser = $this->getApiUserFixture();
        $queryString = $this->queryParamsToQueryString($this->getExportTestQueryParams());

        $this->makeSignedRequestAndGetResponse(self::ENDPOINT, $this->getExportTestQueryParams(), [
            'signature' => $this->signString($queryString, $apiUser) . "invalid",
            'expectedResponseCode' => 401,
        ]);
    }

    public function testReplay()
    {
        $queryParams = $this->getExportTestQueryParams();
        $this->makeSignedRequestAndGetResponse(self::ENDPOINT, $queryParams, ['expectedResponseCode' => 200], addNonce: false);
        $this->makeSignedRequestAndGetResponse(self::ENDPOINT, $queryParams, ['expectedResponseCode' => 401], addNonce: false);
    }

    public function timestampsData(): array
    {
        return [
            "too old" => [fn() => (time() - 31), 401],
            "old but ok" => [fn() => (time() - 29), 200],
            "too far in the future" => [fn() => (time() + 6), 401],
            "in the future but ok" => [fn() => (time() + 4), 200],
        ];
    }

    /**
     * @dataProvider timestampsData
     */
    public function testTimestamps(\Closure $timestamp, int $expectedCode)
    {
        $queryParams = $this->getExportTestQueryParams($timestamp());
        $this->makeSignedRequestAndGetResponse(self::ENDPOINT, $queryParams, ['expectedResponseCode' => $expectedCode]);
    }

    public function validAuthRequestsData(): array
    {
        return [
            "empty queryString" => [[], 401],
            "missing start/end times" => [['timestamp'], 400],
            "valid queryString" => [['timestamp', 'startTime', 'endTime'], 200],
        ];
    }

    /**
     * @dataProvider validAuthRequestsData
     */
    public function testValidAuth(array $variablesToInclude, int $expectedCode)
    {
        $queryParams = array_filter($this->getExportTestQueryParams(), fn($key) => in_array($key, $variablesToInclude), ARRAY_FILTER_USE_KEY);
        $this->makeSignedRequestAndGetResponse(self::ENDPOINT, $queryParams, ['expectedResponseCode' => $expectedCode, 'noDefaultQueryParams' => true]);
    }
}