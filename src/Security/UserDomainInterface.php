<?php

namespace App\Security;

interface UserDomainInterface
{
    public function getDomain(): ?string;
}