<?php

namespace App\Utility\Security;

class UrlSigner
{
    public const REASON_HASH_INVALID = 'hash-invalid';
    public const REASON_EXPIRED = 'expired';

    protected ?string $lastInvalidReason = null;

    public function __construct(protected string $secret, protected string $algorithm = 'ripemd128')
    {
    }

    public function sign(string $url, int $validFor = 0, ?int $currentTime=null): string {
        $currentTime ??= time();
        $encodedUrl = new Url($url);
        $until = $validFor ? ($currentTime + $validFor) : 0;

        $hash = $this->calculateUrlHash($encodedUrl, $until);

        $encodedUrl->setQueryParam('_signature', $hash);
        if ($until) {
            $encodedUrl->setQueryParam('_until', $until);
        }

        return $encodedUrl->__toString();
    }

    public function isValid(string $url, int $currentTime=null): bool {
        unset($this->lastInvalidReason);
        $currentTime ??= time();
        $signedUrl = new Url($url);

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
}