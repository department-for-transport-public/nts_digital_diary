<?php

namespace App\Tests\Functional\Api;

use App\Entity\ApiUser;
use App\Security\HmacAuth\SecretGenerator;
use App\Tests\Functional\AbstractFunctionalWebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Component\DomCrawler\Crawler;

abstract class AbstractApiWebTestCase extends AbstractFunctionalWebTestCase
{
    protected EntityManagerInterface $entityManager;
    private ?SecretGenerator $secretGenerator;

    protected function setUp(): void
    {
        if (!isset($this->client)) {
            throw new RuntimeException("AbstractApiWebTestCase::setUp must be run after initialiseClientAndLoadFixtures");
        }

        $container = static::getContainer();
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->secretGenerator = $container->get(SecretGenerator::class);
    }

    public function garbleId(string $interviewerId): string
    {
        return strtr($interviewerId, "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789", "BCDEFGHIJKLMNOPQRSTUVWXYZ0123456789A");
    }

    protected function makeSignedRequest(string $endpoint, array $queryParams = [], array $options = [], array $content = []): ?Crawler
    {
        if (!($options['noDefaultQueryParams'] ?? null)) {
            $queryParams['timestamp'] ??= time();
        }

        $apiUser = $this->getApiUserFixture();
        $queryString = $this->queryParamsToQueryString($queryParams);

        $server = [
            'HTTP_X_AUTH_KEY' => $apiUser->getKey(),
            'HTTP_X_AUTH_SIGNATURE' => $options['signature'] ?? $this->signString($queryString, $apiUser),
        ];

        $jsonContent = empty($content) ? null : json_encode($content);
        if ($jsonContent) {
            $server['HTTP_ACCEPT'] = $server['CONTENT_TYPE'] = 'application/json';
        }

        return $this->client->request($options['method'] ?? 'GET', "{$endpoint}?{$queryString}", [], [], $server, $jsonContent);
    }

    protected function makeSignedRequestAndGetResponse(string $endPoint, array $queryParams = [], array $options = [], array $data = [], bool $addNonce = true): ?array
    {
        $expectedResponseCode = $options['expectedResponseCode'] ?? 200;
        $expectBody = $expectedResponseCode === 200;

        if ($addNonce && !isset($queryParams['_nonce'])) {
            try {
                $queryParams['_nonce'] = bin2hex(random_bytes(4));
            } catch (\Throwable $e) {
            }
        }
        $this->makeSignedRequest($endPoint, $queryParams, $options, $data);
        self::assertResponseStatusCodeSame($expectedResponseCode);

        if (!$expectBody) {
            return null;
        }

        self::assertStringStartsWith('application/json', $this->client->getResponse()->headers->get('content-type'));
        return json_decode($this->client->getResponse()->getContent(), true);
    }

    protected function signString($string, ApiUser $apiUser): string
    {
        $secret = $this->secretGenerator->getSecretForApiUser($apiUser);
        return base64_encode(hash_hmac('sha256', $string, $secret, true));
    }

    protected function getExportTestQueryParams($timestamp = null, $startTime = null, $endTime = null): array
    {
        $timestamp = intval($timestamp ?? time());
        return [
            'timestamp' => $timestamp,
            'startTime' => $startTime ?? ($timestamp - (60 * 24 * 60 * 60)),
            'endTime' => $endTime ?? ($timestamp - (53 * 24 * 60 * 60)),
        ];
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

    protected function queryParamsToQueryString(array $queryParams): string
    {
        $queryStringParts = [];
        foreach ($queryParams as $key => $value) {
            $queryStringParts[] = "{$key}={$value}";
        }
        return join('&', $queryStringParts);
    }
}