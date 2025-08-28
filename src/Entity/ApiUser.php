<?php

namespace App\Entity;

use App\Repository\ApiUserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: ApiUserRepository::class)]
class ApiUser implements UserInterface
{
    public const ROLE_API_USER = 'ROLE_API_USER';

    use IdTrait;

    #[ORM\Column(name: 'key_', type: 'string', length: 255)]
    private ?string $key = null;

    #[ORM\Column(type: 'integer')]
    private ?int $nonce = null;

    public function getKey(): ?string
    {
        return $this->key;
    }

    public function setKey(string $key): self
    {
        $this->key = $key;
        return $this;
    }

    public function getNonce(): ?int
    {
        return $this->nonce;
    }

    public function setNonce(int $nonce): self
    {
        $this->nonce = $nonce;
        return $this;
    }

    public function getRoles(): array
    {
        return [
            self::ROLE_API_USER,
        ];
    }

    public function getPassword(): ?string
    {
        return null;
    }

    public function getSalt(): ?string
    {
        return null;
    }

    public function eraseCredentials(): void
    {
    }

    public function getUsername(): ?string
    {
        return $this->getKey();
    }

    public function getUserIdentifier(): string
    {
        $key = $this->getKey();

        if (is_null($key)) {
            throw new \RuntimeException('Cannot get userIdentifier before it has been set');
        }

        return $key;
    }
}
