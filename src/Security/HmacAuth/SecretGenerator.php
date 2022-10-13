<?php

namespace App\Security\HmacAuth;

use App\Entity\ApiUser;

class SecretGenerator
{
    protected string $secret;

    public function __construct(string $secret)
    {
        $this->secret = $secret;
    }

    /**
     * @return mixed
     */
    public function getSecretForApiUser(ApiUser $apiUser)
    {
        return hash_hmac('sha256', $apiUser->getKey().$apiUser->getNonce(), $this->secret, true);
    }
}
