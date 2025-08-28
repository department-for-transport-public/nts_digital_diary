<?php

namespace App\Security\HmacAuth;

use App\Entity\ApiUser;

class SecretGenerator
{
    public function __construct(protected string $secret)
    {
    }

    public function getSecretForApiUser(ApiUser $apiUser): string
    {
        return hash_hmac('sha256', $apiUser->getKey().$apiUser->getNonce(), $this->secret, true);
    }
}
