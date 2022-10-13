<?php


namespace App\Tests\Functional\ExportApi;


use App\Tests\DataFixtures\ExportApiUserFixtures;

class SecurityTest extends AbstractApiWebTestCase
{
    protected function setUp(): void
    {
        $this->initialiseClientAndLoadFixtures([ExportApiUserFixtures::class]);
        parent::setUp();
    }

    public function testUnsignedRequest()
    {
        $this->client->request('GET', "/api/v1/survey-data?{$this->replacePlaceholders(self::QUERY_STRING)}");
        $this->assertResponseStatusCodeSame(401);
    }

    public function testInvalidSecret()
    {
        $apiUser = $this->getApiUserFixture();
        $this->makeSignedRequest($this->replacePlaceholders(self::QUERY_STRING), $this->signString(self::QUERY_STRING, $apiUser) . "invalid");
        $this->assertResponseStatusCodeSame(401);
    }

    public function testReplay()
    {
        $queryString = $this->replacePlaceholders(self::QUERY_STRING);
        $this->makeSignedRequest($queryString);
        $this->assertResponseStatusCodeSame(200);
        $this->makeSignedRequest($queryString);
        $this->assertResponseStatusCodeSame(401);
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
    public function testTimestamps($timestamp, $expectedCode)
    {
        $timestamp = $timestamp();
        $queryString = $this->replacePlaceholders(self::QUERY_STRING, $timestamp);
        $this->makeSignedRequest($queryString);
        $this->assertResponseStatusCodeSame($expectedCode);
    }




    public function validAuthRequestsData(): array
    {
        return [
            "empty queryString" => ["", 401],
            "missing start/end times" => ["timestamp={timestamp}", 400],
            "missing end time" => ["timestamp={timestamp}&startTime={start}", 400],
            "missing start time" => ["timestamp={timestamp}&endTime={end}", 400],
            "valid queryString" => [self::QUERY_STRING, 200],
        ];
    }

    /**
     * @dataProvider validAuthRequestsData
     */
    public function testValidAuth($queryString, $expectedCode)
    {
        $queryString = $this->replacePlaceholders($queryString);
        $this->makeSignedRequest($queryString);
        $this->assertResponseStatusCodeSame($expectedCode, "code mismatch");
    }
}