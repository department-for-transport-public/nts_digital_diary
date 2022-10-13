<?php


namespace App\Tests\Functional\ExportApi;


use App\Entity\ApiUser;
use App\Security\HmacAuth\SecretGenerator;
use App\Tests\Functional\AbstractFunctionalWebTestCase;
use RuntimeException;
use Symfony\Component\DomCrawler\Crawler;

abstract class AbstractApiWebTestCase extends AbstractFunctionalWebTestCase
{
    private ?SecretGenerator $secretGenerator;
    const QUERY_STRING = "timestamp={timestamp}&startTime={start}&endTime={end}";

    protected function setUp(): void
    {
        if (!isset($this->client)) {
            throw new RuntimeException("AbstractApiWebTestCase::setUp must be run after initialiseClientAndLoadFixtures");
        }
        $this->secretGenerator = static::getContainer()->get(SecretGenerator::class);
    }

    protected function makeSignedRequest($queryString, $signature = null): ?Crawler
    {
        $apiUser = $this->getApiUserFixture();
        return $this->client->request('GET', "/api/v1/survey-data?{$queryString}", [], [], [
            'HTTP_X_AUTH_KEY' => $apiUser->getKey(),
            'HTTP_X_AUTH_SIGNATURE' => $signature ?? $this->signString($queryString, $apiUser),
        ]);
    }

    protected function signString($string, ApiUser $apiUser)
    {
        $secret = $this->secretGenerator->getSecretForApiUser($apiUser);
        return base64_encode(hash_hmac('sha256', $string, $secret, true));
    }

    protected function replacePlaceholders($queryString, $timestamp = null, $start = null, $end = null)
    {
        $timestamp ??= time();
        $start ??= $timestamp - (60 * 24 * 60 * 60);
        $end ??= $timestamp - (30 * 24 * 60 * 60);
        return str_replace(['{timestamp}', '{start}', '{end}'], [$timestamp, $start, $end], $queryString);
    }

    protected function getApiUserFixture(): ApiUser
    {
        /** @var ApiUser $apiUser */
        $apiUser = $this->getFixtureByReference('api-user:1');
        if (!$apiUser instanceof ApiUser) {
            throw new RuntimeException("unexpected reference type");
        }
        return $apiUser;
    }
}