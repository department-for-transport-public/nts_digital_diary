<?php

namespace App\Entity;

use App\Entity\Journey\Method;
use App\Entity\Journey\Stage;
use App\Repository\VehicleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=VehicleRepository::class)
 *
 * @UniqueEntity(groups={"wizard.on-boarding.vehicle"}, message="wizard.on-boarding.vehicle.name.unique", fields={"friendlyName", "household"})
 * @UniqueEntity(groups={"wizard.on-boarding.vehicle"}, message="wizard.on-boarding.vehicle.capi-number.unique", fields={"capiNumber", "household"})
 */
class Vehicle implements \JsonSerializable
{
    public const VALID_METHOD_CODES = [4, 5, 6];

    public const ODOMETER_UNIT_MILES = 'miles';
    public const ODOMETER_UNIT_KILOMETERS = 'kilometers';


    use IdTrait;

    /**
     * @ORM\ManyToOne(targetEntity=Household::class, inversedBy="vehicles")
     * @ORM\JoinColumn(nullable=false)
     */
    private ?Household $household;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank(groups={"wizard.on-boarding.vehicle"}, message="wizard.on-boarding.vehicle.name.not-blank")
     * @Assert\Length(groups={"wizard.on-boarding.vehicle"}, maxMessage="common.string.max-length", max=255)
     */
    private ?string $friendlyName;

    /**
     * @ORM\ManyToOne(targetEntity=DiaryKeeper::class, inversedBy="primaryDriverVehicles")
     * @Assert\NotBlank(groups={"wizard.on-boarding.vehicle"}, message="wizard.on-boarding.vehicle.primary-driver.not-blank")
     */
    private ?DiaryKeeper $primaryDriver;

    /**
     * @ORM\ManyToOne(targetEntity=Method::class)
     * @ORM\JoinColumn(nullable=false)
     * @Assert\Expression("this.getMethod() && (this.getMethod().getCode() in constant('App\\Entity\\Vehicle::VALID_METHOD_CODES'))")
     * @Assert\NotBlank(groups={"wizard.on-boarding.vehicle"}, message="wizard.on-boarding.vehicle.method.not-blank")
     */
    private ?Method $method;

    /**
     * @var Collection<Stage>
     * @ORM\OneToMany(targetEntity=Stage::class, mappedBy="vehicle")
     */
    private Collection $stages;

    /**
     * @ORM\Column(type="integer")
     * @Assert\Range(min=1, max=99, groups={"wizard.on-boarding.vehicle"}, notInRangeMessage="wizard.on-boarding.vehicle.capi-number.not-in-range")
     * @Assert\NotBlank(groups={"wizard.on-boarding.vehicle"}, message="wizard.on-boarding.vehicle.capi-number.not-blank")
     */
    private ?int $capiNumber;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $weekStartOdometerReading;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Assert\Expression("isNull(value) || value >= this.getWeekStartOdometerReading()", groups={"vehicle.odometer-readings"}, message="vehicle.odometer-readings.end-not-less-than-start")
     */
    private ?int $weekEndOdometerReading;

    /**
     * @ORM\Column(type="string", length=12, nullable=true)
     */
    private ?string $odometerUnit;


    public function __construct()
    {
        $this->stages = new ArrayCollection();
    }

    public function getId(): ?string
    {
        return $this->id;
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

    public function getPrimaryDriver(): ?DiaryKeeper
    {
        return $this->primaryDriver;
    }

    public function setPrimaryDriver(?DiaryKeeper $primaryDriver): self
    {
        $this->primaryDriver = $primaryDriver;
        return $this;
    }

    public function getMethod(): ?Method
    {
        return $this->method ?? null;
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
        return $this->friendlyName ?? null;
    }

    public function getCapiNumber(): ?int
    {
        return $this->capiNumber;
    }

    public function setCapiNumber(?int $capiNumber): self
    {
        $this->capiNumber = $capiNumber;

        return $this;
    }

    public function getWeekStartOdometerReading(): ?int
    {
        return $this->weekStartOdometerReading;
    }

    public function setWeekStartOdometerReading(?int $weekStartOdometerReading): self
    {
        $this->weekStartOdometerReading = $weekStartOdometerReading;

        return $this;
    }

    public function getWeekEndOdometerReading(): ?int
    {
        return $this->weekEndOdometerReading;
    }

    public function setWeekEndOdometerReading(?int $weekEndOdometerReading): self
    {
        $this->weekEndOdometerReading = $weekEndOdometerReading;

        return $this;
    }

    public function getOdometerUnit(): ?string
    {
        return $this->odometerUnit;
    }

    public function setOdometerUnit(?string $odometerUnit): self
    {
        $this->odometerUnit = $odometerUnit;

        return $this;
    }
}