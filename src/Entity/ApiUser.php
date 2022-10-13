<?php

namespace App\Entity;

use App\Repository\ApiUserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\Entity(repositoryClass=ApiUserRepository::class)
 */
class ApiUser implements UserInterface
{
    use IdTrait;

    /**
     * @ORM\Column(type="string", length=255, name="key_")
     */
    private ?string $key;

    /**
     * @ORM\Column(type="integer")
     */
    private ?int $nonce;

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
            'ROLE_API_USER'
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

    public function eraseCredentials()
    {
    }

    public function getUsername(): ?string
    {
        return $this->getKey();
    }

    public function getUserIdentifier(): ?string
    {
        return $this->getKey();
    }
}
