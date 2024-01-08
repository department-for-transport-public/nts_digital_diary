<?php

namespace App\Utility\Security;

use App\Utility\Security\Url;

class UrlSigner
{
    protected string $secret;
    protected string $algorithm;

    public function __construct(string $secret, string $algorithm = 'ripemd128')
    {
        $this->secret = $secret;
        $this->algorithm = $algorithm;
    }

    public function sign(string $url, int $validFor = 0, int $currentTime=null): string {
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
        $currentTime ??= time();
        $signedUrl = new Url($url);

        $until = intval($signedUrl->getQueryParam('_until')) ?? 0;
        $signature = $signedUrl->getQueryParam('_signature') ?? '';

        $calculatedHash = $this->calculateUrlHash($signedUrl, $until);

        if (!hash_equals($signature, $calculatedHash)) {
            return false;
        }

        return ($until === 0 || $until > $currentTime);
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