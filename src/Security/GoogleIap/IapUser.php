<?php

namespace App\Security\GoogleIap;

use App\Security\UserDomainInterface;
use App\Security\UserDomainTrait;
use Symfony\Component\Security\Core\User\UserInterface;

class IapUser implements UserInterface, UserDomainInterface
{
    use UserDomainTrait;

    public function __construct(
        protected string  $username,
        protected ?string $password,
        protected array   $roles = []
    ) {
        // Any user that can log in gets this...
        $this->roles[] = 'ROLE_ADMIN';
    }

    public function __toString(): string
    {
        return $this->getUserIdentifier();
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getUserIdentifier(): string
    {
        return $this->username;
    }

    public function getUsername(): string
    {
        return $this->getUserIdentifier();
    }

    public function eraseCredentials(): void
    {
        $this->password = null;
    }

    public function getSalt(): ?string
    {
        return null;
    }
}