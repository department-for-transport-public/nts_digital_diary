<?php

namespace App\Security;

trait UserDomainTrait
{
    public function getDomain(): ?string
    {
        $emailAddress = $this->getUserIdentifier();
        $emailParts = explode('@', $emailAddress);

        if (count($emailParts) !== 2) {
            return null;
        }

        return $emailParts[1];
    }
}