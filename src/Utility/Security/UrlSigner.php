<?php

namespace App\Utility\Security;

use Symfony\Component\RateLimiter\LimiterInterface;

class UrlSigner
{
    public const array EXCLUDE_PARAMETERS_FROM_SIGNATURE = [
        self::SOURCE_TRACKING_PARAMETER_NAME,
    ];
    public const string SOURCE_TRACKING_PARAMETER_NAME = 'src_mode';

    public const string REASON_HASH_INVALID = 'hash-invalid';
    public const string REASON_EXPIRED = 'expired';
    public const string REASON_USED_TOO_MANY_TIMES = 'used-too-many-times';

    protected ?string $lastInvalidReason = null;

    public function __construct(
        protected string $secret,
        protected string $algorithm = 'ripemd128',
    ) {}

    public function sign(string $url, int $validFor = 0, ?int $currentTime=null): string {
        $currentTime ??= time();
        $encodedUrl = new Url($url);
        $excludedParams = $this->excludeQueryParams($encodedUrl);

        $until = $validFor ? ($currentTime + $validFor) : 0;

        $hash = $this->calculateUrlHash($encodedUrl, $until);

        $encodedUrl->setQueryParam('_signature', $hash);
        if ($until) {
            $encodedUrl->setQueryParam('_until', $until);
        }

        $this->addQueryParams($encodedUrl, $excludedParams);
        return $encodedUrl->__toString();
    }

    public function isValid(string $url, int $currentTime=null, ?LimiterInterface $rateLimiter = null): bool {
        unset($this->lastInvalidReason);
        $currentTime ??= time();
        $signedUrl = new Url($url);
        $this->excludeQueryParams($signedUrl);

        $until = intval($signedUrl->getQueryParam('_until')) ?? 0;
        $signature = $signedUrl->getQueryParam('_signature') ?? '';

        $calculatedHash = $this->calculateUrlHash($signedUrl, $until);

        if (!hash_equals($signature, $calculatedHash)) {
            $this->lastInvalidReason = self::REASON_HASH_INVALID;
            return false;
        }

        if ($until !== 0 && $until <= $currentTime) {
            $this->lastInvalidReason = self::REASON_EXPIRED;
            return false;
        }

        if ($rateLimiter !== null) {
            if (!$rateLimiter->consume()->isAccepted()) {
                $this->lastInvalidReason = self::REASON_USED_TOO_MANY_TIMES;
                return false;
            }
        }

        return true;
    }

    public function getLastInvalidReason(): ?string
    {
        return $this->lastInvalidReason ?? null;
    }

    protected function calculateUrlHash(Url $url, int $until): string
    {
        // Extract just the path and query parts of the URL, additionally removing the _until and _signature params.
        // The hash is based upon what remains, and the $until argument.
        $pathAndParams = (clone $url)
            ->removeQueryParam('_signature')
            ->removeQueryParam('_until')
            ->setScheme(null)
            ->setUser(null)
            ->setPort(null)
            ->setPass(null)
            ->setHost(null)
            ->setFragment(null)
            ->__toString();

        return hash_hmac($this->algorithm, "{$until}:{$pathAndParams}", $this->secret);
    }

    protected function excludeQueryParams(Url $url): array
    {
        $removedParams = [];
        foreach (self::EXCLUDE_PARAMETERS_FROM_SIGNATURE as $param) {
            if ($removedValue = $url->getQueryParam($param))  $removedParams[$param] = $removedValue;
            $url->removeQueryParam($param);
        }
        return $removedParams;
    }

    protected function addQueryParams(Url $url, array $params): void
    {
        foreach ($params as $param => $value) {
            if ($value) {
                $url->setQueryParam($param, $value);
            }
        }
    }
}