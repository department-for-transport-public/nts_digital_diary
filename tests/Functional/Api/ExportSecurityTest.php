<?php

namespace App\Tests\Functional\Api;

use App\Tests\DataFixtures\ApiUserFixtures;
use Symfony\Component\HttpFoundation\Request;

class ExportSecurityTest extends AbstractApiWebTestCase
{
    protected const ENDPOINT = '/api/v1/survey-data';

    protected function setUp(): void
    {
        $this->initialiseClientAndLoadFixtures([ApiUserFixtures::class]);
        parent::setUp();
    }

    public function testUnsignedRequest(): void
    {
        $queryString = $this->queryParamsToQueryString($this->getExportTestQueryParams());
        $this->client->request(Request::METHOD_GET, self::ENDPOINT."?{$queryString}");
        $this->assertResponseStatusCodeSame(401);
    }

    public function testInvalidSecret(): void
    {
        $apiUser = $this->getApiUserFixture();
        $queryString = $this->queryParamsToQueryString($this->getExportTestQueryParams());

        $this->makeSignedRequestAndGetResponse(self::ENDPOINT, $this->getExportTestQueryParams(), [
            'signature' => $this->signString($queryString, $apiUser) . "invalid",
            'expectedResponseCode' => 401,
        ]);
    }

    public function testReplay(): void
    {
        $queryParams = $this->getExportTestQueryParams();
        $this->makeSignedRequestAndGetResponse(self::ENDPOINT, $queryParams, ['expectedResponseCode' => 200], addNonce: false);
        $this->makeSignedRequestAndGetResponse(self::ENDPOINT, $queryParams, ['expectedResponseCode' => 401], addNonce: false);
    }

    public function timestampsData(): array
    {
        return [
            "too old" => [fn(): int => (time() - 31), 401],
            "old but ok" => [fn(): int => (time() - 29), 200],
            "too far in the future" => [fn(): int => (time() + 6), 401],
            "in the future but ok" => [fn(): int => (time() + 4), 200],
        ];
    }

    /**
     * @dataProvider timestampsData
     */
    public function testTimestamps(\Closure $timestamp, int $expectedCode): void
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
    public function testValidAuth(array $variablesToInclude, int $expectedCode): void
    {
        $queryParams = array_filter($this->getExportTestQueryParams(), fn($key): bool => in_array($key, $variablesToInclude), ARRAY_FILTER_USE_KEY);
        $this->makeSignedRequestAndGetResponse(self::ENDPOINT, $queryParams, ['expectedResponseCode' => $expectedCode, 'noDefaultQueryParams' => true]);
    }
}