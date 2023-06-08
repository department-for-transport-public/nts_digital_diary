<?php

namespace App\Entity;

use App\Repository\OtpUserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\Ignore;

/**
 * @ORM\Entity(repositoryClass=OtpUserRepository::class)
 */
class OtpUser implements UserInterface, OtpUserInterface
{
    use IdTrait;

    /**
     * @ORM\Column(type="string", length=10)
     */
    private string $userIdentifier;

    /**
     * @ORM\ManyToOne(targetEntity=AreaPeriod::class, inversedBy="otpUsers")
     * @ORM\JoinColumn(nullable=false)
     */
    private ?AreaPeriod $areaPeriod;

    /**
     * @ORM\OneToOne(targetEntity=Household::class, cascade={"persist"})
     */
    private ?Household $household;

    public function getUserIdentifier(): ?string
    {
        return $this->userIdentifier ?? null;
    }

    public function setUserIdentifier(string $userIdentifier): self
    {
        $this->userIdentifier = $userIdentifier;

        return $this;
    }

    public function getAreaPeriod(): ?AreaPeriod
    {
        return $this->areaPeriod ?? null;
    }

    public function setAreaPeriod(?AreaPeriod $areaPeriod): self
    {
        $this->areaPeriod = $areaPeriod;

        return $this;
    }

    public function getRoles(): array
    {
        return [
            'ROLE_ON_BOARDING',
        ];
    }

    /**
     * @return string|void|null
     */
    public function getPassword()
    {
    }

    /**
     * @return string|void|null
     */
    public function getSalt()
    {
    }

    public function eraseCredentials()
    {
    }

    #[Ignore]
    public function getUsername(): string
    {
        return $this->getUserIdentifier();
    }

    /**
     * we need serialize and unserialize to prevent the whole entity tree being
     * dumped in the session
     */
    public function __serialize(): array
    {
        return [$this->id, $this->userIdentifier];
    }

    public function __unserialize($data)
    {
        [$this->id, $this->userIdentifier] = $data;
    }

    public function getHousehold(): ?Household
    {
        return $this->household ?? null;
    }

    public function setHousehold(Household $household): self
    {
        $this->household = $household;

        return $this;
    }

}
