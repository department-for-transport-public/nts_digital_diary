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

#[UniqueEntity(fields: ['friendlyName', 'household'], message: 'wizard.on-boarding.vehicle.name.unique', groups: ['wizard.on-boarding.vehicle'])]
#[UniqueEntity(fields: ['capiNumber', 'household'], message: 'wizard.on-boarding.vehicle.capi-number.unique', groups: ['wizard.on-boarding.vehicle'])]
#[ORM\Entity(repositoryClass: VehicleRepository::class)]
class Vehicle implements \JsonSerializable
{
    public const VALID_METHOD_KEYS = [
        'car',
        'motorcycle',
        'van-or-lorry',
        'other-private',
    ];

    public const ODOMETER_UNIT_MILES = 'miles';
    public const ODOMETER_UNIT_KILOMETERS = 'kilometers';


    use IdTrait;

    #[ORM\ManyToOne(targetEntity: Household::class, inversedBy: 'vehicles')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Household $household = null;

    #[Assert\NotBlank(message: 'wizard.on-boarding.vehicle.name.not-blank', groups: ['wizard.on-boarding.vehicle', 'direct-onboarding.ingress'])]
    #[Assert\Length(max: 255, maxMessage: 'common.string.max-length', groups: ['wizard.on-boarding.vehicle', 'direct-onboarding.ingress'])]
    #[ORM\Column(type: 'string', length: 255)]
    private ?string $friendlyName = null;

    #[Assert\NotBlank(message: 'wizard.on-boarding.vehicle.primary-driver.not-blank', groups: ['wizard.on-boarding.vehicle', 'direct-onboarding.ingress'])]
    #[ORM\ManyToOne(targetEntity: DiaryKeeper::class, inversedBy: 'primaryDriverVehicles')]
    private ?DiaryKeeper $primaryDriver = null;

    #[Assert\Expression("!this.getMethod() or (this.getMethod().getDescriptionTranslationKey() in constant('App\\\\Entity\\\\Vehicle::VALID_METHOD_KEYS'))", groups: ['direct-onboarding.ingress'])]
    #[Assert\NotBlank(message: 'wizard.on-boarding.vehicle.method.not-blank', groups: ['wizard.on-boarding.vehicle', 'direct-onboarding.ingress'])]
    #[ORM\ManyToOne(targetEntity: Method::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Method $method = null;

    /**
     * @var Collection<Stage>
     */
    #[ORM\OneToMany(mappedBy: 'vehicle', targetEntity: Stage::class)]
    private Collection $stages;

    #[Assert\Range(notInRangeMessage: 'wizard.on-boarding.vehicle.capi-number.not-in-range', min: 1, max: 99, groups: ['wizard.on-boarding.vehicle', 'direct-onboarding.ingress'])]
    #[Assert\NotBlank(message: 'wizard.on-boarding.vehicle.capi-number.not-blank', groups: ['wizard.on-boarding.vehicle', 'direct-onboarding.ingress'])]
    #[ORM\Column(type: 'integer')]
    private ?int $capiNumber = null;

    #[Assert\Positive(groups: ['vehicle.odometer-readings'])]
    #[Assert\Range(maxMessage: 'common.number.max', max: 10000000, groups: ['vehicle.odometer-readings'])]
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $weekStartOdometerReading = null;

    #[Assert\Positive(groups: ['vehicle.odometer-readings'])]
    #[Assert\Expression('isNull(value) || value < 0 || value >= this.getWeekStartOdometerReading()', message: 'vehicle.odometer-readings.end-not-less-than-start', groups: ['vehicle.odometer-readings'])]
    #[Assert\Range(maxMessage: 'common.number.max', max: 10000000, groups: ['vehicle.odometer-readings'])]
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $weekEndOdometerReading = null;

    #[Assert\Expression('!isNull(value) || (isNull(this.getWeekStartOdometerReading()) && isNull(this.getWeekEndOdometerReading()))', message: 'vehicle.odometer-readings.units-required', groups: ['vehicle.odometer-readings'])]
    #[ORM\Column(type: 'string', length: 12, nullable: true)]
    private ?string $odometerUnit = null;


    public function __construct()
    {
        $this->stages = new ArrayCollection();
    }

    public function getId(): ?string
    {
        return $this->id ?? null;
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
