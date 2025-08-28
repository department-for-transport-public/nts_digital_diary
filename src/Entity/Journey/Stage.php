<?php

namespace App\Entity\Journey;

use App\Entity\BasicMetadataInterface;
use App\Entity\BasicMetadataTrait;
use App\Entity\Embeddable\CostOrNil;
use App\Entity\Embeddable\Distance;
use App\Entity\IdTrait;
use App\Entity\PropertyChangeLoggableInterface;
use App\Entity\Vehicle;
use App\Repository\Journey\StageRepository;
use App\Validator\Constraints as AppAssert;
use App\Validator\JourneySharingValidator;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[Assert\Callback([JourneySharingValidator::class, 'validateStage'], groups: ['wizard.share-journey.driver-and-parking', 'wizard.share-journey.ticket-type-and-cost'])]
#[ORM\Entity(repositoryClass: StageRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Stage implements PropertyChangeLoggableInterface, BasicMetadataInterface
{
    use IdTrait;
    use BasicMetadataTrait;

    public const COMMON_PROPERTIES = [
        /* method form */   'method', 'methodOther',
        /* details form */  'distanceTravelled', 'travelTime', 'adultCount', 'childCount',
        /* vehicle form */  'vehicle', 'vehicleOther',
        /* ticket form */   'ticketType',
    ];

    public const SHARE_JOURNEY_CLONE_PROPERTIES = [
        'number',
        ...self::COMMON_PROPERTIES,
        /* cost form */     'boardingCount',
    ];
    public const REPEAT_JOURNEY_CLONE_PROPERTIES = [
        'number',
        ...self::COMMON_PROPERTIES,
        /* driver form */   'isDriver', 'parkingCost',
        /* cost form */     'ticketCost', 'boardingCount',
    ];
    public const RETURN_JOURNEY_CLONE_PROPERTIES = [
        ...self::COMMON_PROPERTIES,
        /* driver form (without parkingCost) */   'isDriver',
        /* cost form (without ticketCost) */     'boardingCount',
    ];

    public const MERGE_PROPERTIES = [
        'journey',
        ...self::COMMON_PROPERTIES,
        /* driver form */   'isDriver', 'parkingCost',
        /* cost form */     'ticketCost', 'boardingCount',
    ];

    #[ORM\ManyToOne(targetEntity: Journey::class, inversedBy: 'stages')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Journey $journey = null;

    #[Assert\NotNull(message: 'wizard.stage.method.not-null', groups: ['wizard.stage.method'])]
    #[ORM\ManyToOne(targetEntity: Method::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Method $method = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $methodOther = null;

    #[Assert\Callback(groups: ['wizard.stage.method'])]
    public function validateMethodOther(ExecutionContextInterface $context): void
    {
        if ($this->getMethod() && $this->getMethod()->isOtherRequired()) {
            $transKey = $this->getMethod()->getDescriptionTranslationKey();
            if (!$this->getMethodOther()) {
                $context->buildViolation("wizard.stage.method-other.$transKey.not-empty")
                    ->atPath("other-$transKey")
                    ->addViolation();
            } else if (mb_strlen((string) $this->methodOther, 'UTF-8') > 255) {
                $context->buildViolation("common.string.max-length")
                    ->setParameter('{{ limit }}', 255)
                    ->atPath("other-$transKey")
                    ->addViolation();
            }
        }
    }

    #[AppAssert\ValidValueUnit(translationPrefix: "wizard.stage.distance-travelled", allowBlank: true, isDecimal: true, decimalScale: 2, groups: ["wizard.stage.details", "wizard.repeat-journey.stage-details"])]
    #[ORM\Embedded(class: Distance::class)]
    private ?Distance $distanceTravelled = null;

    #[Assert\Positive(message: 'wizard.stage.travel-time.positive', groups: ['wizard.stage.details', 'wizard.repeat-journey.stage-details', 'wizard.return-journey.stage-details'])]
    #[Assert\Range(maxMessage: 'common.number.max', max: 10000, groups: ['wizard.stage.details', 'wizard.repeat-journey.stage-details', 'wizard.return-journey.stage-details'])]
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $travelTime = null;

    #[Assert\Expression('value != 0 or ((isNull(value) or value == 0) and !this.getIsDiaryKeeperAdult())', message: 'wizard.stage.adult-count.at-least-1', groups: ['wizard.stage.details', 'wizard.repeat-journey.stage-details', 'wizard.return-journey.stage-details'])]
    #[Assert\PositiveOrZero(message: 'wizard.stage.adult-count.positive-or-zero', groups: ['wizard.stage.details', 'wizard.repeat-journey.stage-details', 'wizard.return-journey.stage-details'])]
    #[Assert\Range(maxMessage: 'common.number.max', max: 1000, groups: ['wizard.stage.details', 'wizard.repeat-journey.stage-details', 'wizard.return-journey.stage-details'])]
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $adultCount = null;

    #[Assert\Expression('value != 0 or ((isNull(value) or value == 0) and this.getIsDiaryKeeperAdult())', message: 'wizard.stage.child-count.at-least-1', groups: ['wizard.stage.details', 'wizard.repeat-journey.stage-details', 'wizard.return-journey.stage-details'])]
    #[Assert\PositiveOrZero(message: 'wizard.stage.child-count.positive-or-zero', groups: ['wizard.stage.details', 'wizard.repeat-journey.stage-details', 'wizard.return-journey.stage-details'])]
    #[Assert\Range(maxMessage: 'common.number.max', max: 1000, groups: ['wizard.stage.details', 'wizard.repeat-journey.stage-details', 'wizard.return-journey.stage-details'])]
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $childCount = null;

    #[ORM\ManyToOne(targetEntity: Vehicle::class, inversedBy: 'stages')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Vehicle $vehicle = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $vehicleOther = null;

    #[Assert\Callback(groups: ['wizard.vehicle'])]
    public function validateVehicleAndVehicleOther(ExecutionContextInterface $context): void
    {
        if ($this->vehicle === null) {
            $transPrefix = "wizard.stage.vehicle";

            if ($this->vehicleOther === '') {
                $context
                    ->buildViolation("{$transPrefix}.vehicle-other.not-empty")
                    ->atPath('vehicleOther')
                    ->addViolation();
            } else if (mb_strlen((string) $this->vehicleOther, 'UTF-8') > 255) {
                $context->buildViolation("common.string.max-length")
                    ->setParameter('{{ limit }}', 255)
                    ->atPath("vehicleOther")
                    ->addViolation();
            } else if ($this->vehicleOther === null) {
                $context
                    ->buildViolation("{$transPrefix}.vehicle.not-empty")
                    ->atPath('vehicle')
                    ->addViolation();
            }
        }
    }

    #[Assert\NotNull(message: 'wizard.stage.driver-or-passenger.not-null', groups: ['wizard.stage.driver-and-parking'])]
    #[Assert\NotNull(message: 'wizard.share-journey.is-driver.not-null', groups: ['wizard.share-journey.driver-and-parking.entry'])]
    #[Assert\Expression('value != true || this.getDriverCountForSharedStage() <= 1', message: 'wizard.share-journey.is-driver.not-multiple', groups: ['wizard.share-journey.driver-and-parking.entry'])]
    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $isDriver = null;

    /**
     * Cost in pence
     */
    #[AppAssert\CostOrNil(translationPrefix: "wizard.stage.parking-cost", allowBlankCost: true, groups: ["wizard.stage.driver-and-parking", "wizard.return-journey.stage-details"])]
    #[AppAssert\CostOrNil(translationPrefix: "wizard.share-journey.parking-cost", allowBlankCost: true, groups: ["wizard.share-journey.driver-and-parking.entry"])]
    #[ORM\Embedded(class: CostOrNil::class)]
    private ?CostOrNil $parkingCost = null;

    #[Assert\NotNull(message: 'wizard.stage.ticket-type.not-null', groups: ['wizard.ticket-type'])]
    #[Assert\NotNull(message: 'wizard.share-journey.ticket-type.not-null', groups: ['wizard.share-journey.ticket-type-and-cost.entry'])]
    #[Assert\Length(max: 255, maxMessage: 'common.string.max-length', groups: ['wizard.ticket-type', 'wizard.share-journey.ticket-type-and-cost.entry'])]
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $ticketType = null;

    #[AppAssert\CostOrNil(translationPrefix: "wizard.stage.ticket-cost", allowBlankCost: true, groups: ["wizard.ticket-cost-and-boardings", "wizard.return-journey.stage-details"])]
    #[AppAssert\CostOrNil(translationPrefix: "wizard.share-journey.ticket-cost", allowBlankCost: true, groups: ["wizard.share-journey.ticket-type-and-cost.entry"])]
    #[ORM\Embedded(class: CostOrNil::class)]
    private ?CostOrNil $ticketCost = null;

    #[Assert\NotBlank(message: 'wizard.stage.boarding-count.not-blank', groups: ['wizard.ticket-cost-and-boardings'])]
    #[Assert\Positive(message: 'wizard.stage.boarding-count.positive', groups: ['wizard.ticket-cost-and-boardings'])]
    #[Assert\Range(maxMessage: 'common.number.max', max: 1000, groups: ['wizard.ticket-cost-and-boardings'])]
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $boardingCount = null;

    #[ORM\Column(type: 'integer')]
    private ?int $number = null;

    #[ORM\PrePersist]
    public function autoAssignNumber(): self
    {
        if (!isset($this->number)) {
            $highestStageSort = 0;
            $this->number = 0;
            foreach ($this->journey->getStages() as $stage) {
                $highestStageSort = max($highestStageSort, $stage->getNumber());
            }
            $this->number = $highestStageSort + 1;
        }
        return $this;
    }

    #[ORM\PreRemove]
    public function setSiblingNumbersPreRemoval(): void
    {
        $journey = $this->journey;

        if (!$journey) {
            return;
        }

        // don't clear the Journey on this stage, because the DiaryKeeperStateSubscriber (changes the state of the DK
        // when they edit) needs to know the day number
        $journey->removeStage($this, false);
        $siblings = $journey->getStages()->toArray();

        usort($siblings, fn(Stage $a, Stage $b): int => $a->getNumber() <=> $b->getNumber());

        foreach($siblings as $idx => $sibling) {
            $sibling->setNumber($idx + 1);
        }
    }

    public function getJourney(): ?Journey
    {
        return $this->journey ?? null;
    }

    public function setJourney(?Journey $journey): self
    {
        $this->journey = $journey;
        return $this;
    }

    public function getDistanceTravelled(): ?Distance
    {
        return $this->distanceTravelled ?? null;
    }

    public function setDistanceTravelled(?Distance $distanceTravelled): self
    {
        $this->distanceTravelled = $distanceTravelled;
        return $this;
    }

    public function getTravelTime(): ?int
    {
        return $this->travelTime ?? null;
    }

    public function setTravelTime(?int $travelTime): self
    {
        $this->travelTime = $travelTime;
        return $this;
    }

    public function getAdultCount(): ?int
    {
        return $this->adultCount ?? null;
    }

    public function setAdultCount(?int $adultCount): self
    {
        $this->adultCount = $adultCount;
        return $this;
    }

    public function getChildCount(): ?int
    {
        return $this->childCount ?? null;
    }

    public function setChildCount(?int $childCount): self
    {
        $this->childCount = $childCount;
        return $this;
    }

    public function getVehicle(): ?Vehicle
    {
        return $this->vehicle ?? null;
    }

    public function setVehicle(?Vehicle $vehicle): self
    {
        $this->vehicle = $vehicle;
        return $this;
    }

    public function getIsDriver(): ?bool
    {
        return $this->isDriver ?? null;
    }

    public function setIsDriver(?bool $isDriver): self
    {
        $this->isDriver = $isDriver;
        return $this;
    }

    public function getDriverCountForSharedStage(): int
    {
        $sourceJourney = $this->getJourney()->getSharedFrom();
        $matchingStagesBeingAdded = $sourceJourney->getSharedToJourneysBeingAdded()->map(fn(Journey $j): \App\Entity\Journey\Stage => $j->getStageByNumber($this->getNumber()));
        return array_sum($matchingStagesBeingAdded->map(fn(Stage $s): int => $s->getIsDriver() ? 1 : 0)->toArray());
    }

    public function getParkingCost(): ?CostOrNil
    {
        return $this->parkingCost ?? null;
    }

    public function setParkingCost(?CostOrNil $parkingCost): self
    {
        $this->parkingCost = $parkingCost;
        return $this;
    }

    public function getBoardingCount(): ?int
    {
        return $this->boardingCount ?? null;
    }

    public function setBoardingCount(?int $boardingCount): self
    {
        $this->boardingCount = $boardingCount;
        return $this;
    }

    public function getTicketCost(): ?CostOrNil
    {
        return $this->ticketCost ?? null;
    }

    public function setTicketCost(?CostOrNil $ticketCost): self
    {
        $this->ticketCost = $ticketCost;
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

    public function getMethodForDisplayWithoutTypePrefixWhenOther(): ?TranslatableMessage
    {
        return $this->getMethodForDisplay(true);
    }

    public function getMethodKeyForTranslationParameterInjection(): ?string
    {
        return str_replace("-", "_", $this->method?->getDescriptionTranslationKey());
    }

    public function getMethodForDisplay(bool $omitTypePrefixWhenOther = false): ?TranslatableMessage
    {
        $methodOther = $this->getMethodOther();

        if ($methodOther !== null && $omitTypePrefixWhenOther) {
            $message = "stage.method.choices.other-generic";
        } else {
            $method = $this->getMethod();

            if (!$method) {
                return null;
            }

            $hasOtherSuffix = ($methodOther !== null) ? '-other' : '';
            $message = "stage.method.choices.{$method->getDescriptionTranslationKey()}{$hasOtherSuffix}";
        }

        return new TranslatableMessage(
            $message,
            ['other' => $methodOther],
            'travel-diary'
        );
    }

    public function getMethodForDisplayCompareHousehold(): TranslatableMessage
    {
        $hasOtherSuffix = ($this->methodOther !== null) ? '-other' : '';

        return new TranslatableMessage(
            "compare-household.method-descriptions.{$this->method->getDescriptionTranslationKey()}{$hasOtherSuffix}",
            ['other' => $this->methodOther],
            'interviewer'
        );
    }

    public function getVehicleOther(): ?string
    {
        return $this->vehicleOther ?? null;
    }

    public function setVehicleOther(?string $vehicleOther): self
    {
        $this->vehicleOther = $vehicleOther;
        return $this;
    }

    public function getMethodOther(): ?string
    {
        return $this->methodOther ?? null;
    }

    public function setMethodOther(?string $methodOther): self
    {
        $this->methodOther = $methodOther;
        return $this;
    }

    public function getNumber(): ?int
    {
        return $this->number ?? null;
    }

    public function setNumber(?int $number): self
    {
        $this->number = $number;

        return $this;
    }

    public function getTicketType(): ?string
    {
        return $this->ticketType ?? null;
    }

    public function setTicketType(?string $ticketType): self
    {
        $this->ticketType = $ticketType;
        return $this;
    }

    // -----

    public function getTravellingPeopleCount(): int
    {
        return ($this->adultCount ?? 0) + ($this->childCount ?? 0);
    }

    public function getIsDiaryKeeperAdult(): ?bool
    {
        return $this->getJourney()->getDiaryDay()->getDiaryKeeper()->getIsAdult();
    }
}
