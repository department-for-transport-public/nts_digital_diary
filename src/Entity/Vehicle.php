<?php

namespace App\Entity;

use App\Entity\Journey\Method;
use App\Entity\Journey\Stage;
use App\Repository\VehicleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=VehicleRepository::class)
 */
class Vehicle implements \JsonSerializable
{
    public const VALID_METHOD_CODES = [4, 5, 6];

    use IdTrait;

    /**
     * @deprecated
     * @ORM\Column(type="string", length=10, nullable=true)
     */
    private ?string $registrationNumber = null;

    /**
     * @ORM\ManyToOne(targetEntity=Household::class, inversedBy="vehicles")
     * @ORM\JoinColumn(nullable=false)
     */
    private ?Household $household = null;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank(groups={"wizard.on-boarding.vehicle"}, message="wizard.on-boarding.vehicle.name.not-blank")
     * @Assert\Length(groups={"wizard.on-boarding.vehicle"}, maxMessage="common.string.max-length", max=255)
     */
    private ?string $friendlyName = null;

    /**
     * @ORM\ManyToOne(targetEntity=Method::class)
     * @ORM\JoinColumn(nullable=false)
     * @Assert\Expression("this.getMethod() && (this.getMethod().getCode() in constant('App\\Entity\\Vehicle::VALID_METHOD_CODES'))")
     * @Assert\NotBlank(groups={"wizard.on-boarding.vehicle"}, message="wizard.on-boarding.vehicle.method.not-blank")
     */
    private ?Method $method = null;

    /**
     * @var Collection|Stage[]
     * @ORM\OneToMany(targetEntity=Stage::class, mappedBy="vehicle")
     */
    private Collection $stages;

    public function __construct()
    {
        $this->stages = new ArrayCollection();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * @deprecated
     */
    public function getRegistrationNumber(): ?string
    {
        return $this->registrationNumber;
    }

    /**
     * @deprecated
     */
    public function setRegistrationNumber(?string $registrationNumber): self
    {
        $this->registrationNumber = $registrationNumber;
        return $this;
    }

    public function getHousehold(): ?Household
    {
        return $this->household;
    }

    public function setHousehold(?Household $household): self
    {
        $this->household = $household;
        return $this;
    }

    public function getFriendlyName(): ?string
    {
        return $this->friendlyName;
    }

    public function setFriendlyName(?string $friendlyName): self
    {
        $this->friendlyName = $friendlyName;
        return $this;
    }

    public function getMethod(): ?Method
    {
        return $this->method;
    }

    public function setMethod(?Method $method): self
    {
        $this->method = $method;
        return $this;
    }

    public function getStages(): Collection
    {
        return $this->stages;
    }

    public function setStages(Collection $stages): self
    {
        $this->stages = $stages;
        return $this;
    }

    // -----

    public function jsonSerialize(): ?string
    {
        return $this->friendlyName;
    }
}