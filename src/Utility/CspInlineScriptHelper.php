<?php


namespace App\Utility;


class CspInlineScriptHelper
{
    private string $secret;
    private array $nonces = [];

    public function __construct(string $secret)
    {
        $this->secret = $secret;
    }

    /**
     * @param string $context a context identifier for this nonce
     */
    public function getNonce(string $context): string
    {
        if (!isset($this->nonces[$context])) {
            $rand = rand();
            $this->nonces[$context] = hash_hmac('sha1', "$context-$rand", $this->secret);
        }
        return $this->nonces[$context];
    }
}