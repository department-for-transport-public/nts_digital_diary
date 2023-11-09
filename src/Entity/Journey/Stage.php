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

/**
 * @ORM\Entity(repositoryClass=StageRepository::class)
 * @ORM\HasLifecycleCallbacks()
 *
 * @Assert\Callback({JourneySharingValidator::class, "validateStage"}, groups={"wizard.share-journey.driver-and-parking", "wizard.share-journey.ticket-type-and-cost"})
 */
class Stage implements PropertyChangeLoggableInterface, BasicMetadataInterface
{
    use IdTrait;
    use BasicMetadataTrait;

    const COMMON_PROPERTIES = [
        /* method form */   'method', 'methodOther',
        /* details form */  'distanceTravelled', 'travelTime', 'adultCount', 'childCount',
        /* vehicle form */  'vehicle', 'vehicleOther',
        /* ticket form */   'ticketType',
    ];

    const SHARE_JOURNEY_CLONE_PROPERTIES = [
        'number',
        ...self::COMMON_PROPERTIES,
        /* cost form */     'boardingCount',
    ];
    const REPEAT_JOURNEY_CLONE_PROPERTIES = [
        'number',
        ...self::COMMON_PROPERTIES,
        /* driver form */   'isDriver', 'parkingCost',
        /* cost form */     'ticketCost', 'boardingCount',
    ];
    const RETURN_JOURNEY_CLONE_PROPERTIES = [
        ...self::COMMON_PROPERTIES,
        /* driver form (without parkingCost) */   'isDriver',
        /* cost form (without ticketCost) */     'boardingCount',
    ];

    const MERGE_PROPERTIES = [
        'journey',
        ...self::COMMON_PROPERTIES,
        /* driver form */   'isDriver', 'parkingCost',
        /* cost form */     'ticketCost', 'boardingCount',
    ];

    /**
     * @ORM\ManyToOne(targetEntity=Journey::class, inversedBy="stages")
     * @ORM\JoinColumn(nullable=false)
     */
    private ?Journey $journey;

    /**
     * @ORM\ManyToOne(targetEntity=Method::class)
     * @ORM\JoinColumn(nullable=false)
     * @Assert\NotNull(groups="wizard.stage.method", message="wizard.stage.method.not-null")
     */
    private ?Method $method;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $methodOther;

    /**
     * @Assert\Callback(groups="wizard.stage.method")
     */
    public function validateMethodOther(ExecutionContextInterface $context)
    {
        if ($this->getMethod() && $this->getMethod()->isOtherRequired()) {
            $transKey = $this->getMethod()->getDescriptionTranslationKey();
            if (!$this->getMethodOther()) {
                $context->buildViolation("wizard.stage.method-other.$transKey.not-empty")
                    ->atPath("other-$transKey")
                    ->addViolation();
            } else if (mb_strlen($this->methodOther, 'UTF-8') > 255) {
                $context->buildViolation("common.string.max-length")
                    ->setParameter('{{ limit }}', 255)
                    ->atPath("other-$transKey")
                    ->addViolation();
            }
        }
    }

    /**
     * @ORM\Embedded(class=Distance::class)
     * @AppAssert\ValidValueUnit(
     *     groups={"wizard.stage.details", "wizard.repeat-journey.stage-details"},
     *     translationPrefix="wizard.stage.distance-travelled",
     *     allowBlank=true, isDecimal=true, decimalScale=2
     * )
     */
    private ?Distance $distanceTravelled;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Assert\Positive(
     *     groups={"wizard.stage.details", "wizard.repeat-journey.stage-details", "wizard.return-journey.stage-details"},
     *     message="wizard.stage.travel-time.positive"
     * )
     * @Assert\Range(
     *     groups={"wizard.stage.details", "wizard.repeat-journey.stage-details", "wizard.return-journey.stage-details"},
     *     maxMessage="common.number.max",
     *     max=10000
     * )
     */
    private ?int $travelTime;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Assert\Expression(
     *     groups={"wizard.stage.details", "wizard.repeat-journey.stage-details", "wizard.return-journey.stage-details"},
     *     message="wizard.stage.adult-count.at-least-1", "value != 0 or ((isNull(value) or value == 0) and !this.getIsDiaryKeeperAdult())"
     * )
     * @Assert\PositiveOrZero(
     *     groups={"wizard.stage.details", "wizard.repeat-journey.stage-details", "wizard.return-journey.stage-details"},
     *     message="wizard.stage.adult-count.positive-or-zero"
     * )
     * @Assert\Range(
     *     groups={"wizard.stage.details", "wizard.repeat-journey.stage-details", "wizard.return-journey.stage-details"},
     *     maxMessage="common.number.max",
     *     max=1000
     * )
     */
    private ?int $adultCount;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Assert\Expression(
     *     groups={"wizard.stage.details", "wizard.repeat-journey.stage-details", "wizard.return-journey.stage-details"},
     *     message="wizard.stage.child-count.at-least-1", "value != 0 or ((isNull(value) or value == 0) and this.getIsDiaryKeeperAdult())"
     * )
     * @Assert\PositiveOrZero(
     *     groups={"wizard.stage.details", "wizard.repeat-journey.stage-details", "wizard.return-journey.stage-details"},
     *     message="wizard.stage.child-count.positive-or-zero"
     * )
     * @Assert\Range(
     *     groups={"wizard.stage.details", "wizard.repeat-journey.stage-details", "wizard.return-journey.stage-details"},
     *     maxMessage="common.number.max",
     *     max=1000
     * )
     */
    private ?int $childCount;

    /**
     * @ORM\ManyToOne(targetEntity=Vehicle::class, inversedBy="stages")
     * @ORM\JoinColumn(nullable=true)
     */
    private ?Vehicle $vehicle;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $vehicleOther;

    /**
     * @Assert\Callback(groups="wizard.vehicle")
     */
    public function validateVehicleAndVehicleOther(ExecutionContextInterface $context)
    {
        if ($this->vehicle === null) {
            $transPrefix = "wizard.stage.vehicle";

            if ($this->vehicleOther === '') {
                $context
                    ->buildViolation("{$transPrefix}.vehicle-other.not-empty")
                    ->atPath('vehicleOther')
                    ->addViolation();
            } else if (mb_strlen($this->vehicleOther, 'UTF-8') > 255) {
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

    /**
     * @ORM\Column(type="boolean", nullable=true)
     * @Assert\NotNull(groups={"wizard.stage.driver-and-parking"}, message="wizard.stage.driver-or-passenger.not-null")
     * @Assert\NotNull(groups={"wizard.share-journey.driver-and-parking.entry"}, message="wizard.share-journey.is-driver.not-null")
     * @Assert\Expression("value != true || this.getDriverCountForSharedStage() <= 1", groups="wizard.share-journey.driver-and-parking.entry", message="wizard.share-journey.is-driver.not-multiple")
     */
    private ?bool $isDriver;

    /**
     * Cost in pence
     * @ORM\Embedded(class=CostOrNil::class)
     * @AppAssert\CostOrNil(translationPrefix="wizard.stage.parking-cost", groups={"wizard.stage.driver-and-parking", "wizard.return-journey.stage-details"}, allowBlankCost=true)
     * @AppAssert\CostOrNil(translationPrefix="wizard.share-journey.parking-cost", groups={"wizard.share-journey.driver-and-parking.entry"}, allowBlankCost=true)
     */
    private ?CostOrNil $parkingCost;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\NotNull(groups={"wizard.ticket-type"}, message="wizard.stage.ticket-type.not-null")
     * @Assert\NotNull(groups={"wizard.share-journey.ticket-type-and-cost.entry"}, message="wizard.share-journey.ticket-type.not-null")
     * @Assert\Length(groups={"wizard.ticket-type", "wizard.share-journey.ticket-type-and-cost.entry"}, maxMessage="common.string.max-length", max=255)
     */
    private ?string $ticketType;

    /**
     * @ORM\Embedded(class=CostOrNil::class)
     * @AppAssert\CostOrNil(translationPrefix="wizard.stage.ticket-cost", groups={"wizard.ticket-cost-and-boardings", "wizard.return-journey.stage-details"}, allowBlankCost=true)
     * @AppAssert\CostOrNil(translationPrefix="wizard.share-journey.ticket-cost", groups={"wizard.share-journey.ticket-type-and-cost.entry"}, allowBlankCost=true)
     */
    private ?CostOrNil $ticketCost;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Assert\NotBlank(groups="wizard.ticket-cost-and-boardings", message="wizard.stage.boarding-count.not-blank")
     * @Assert\Positive(groups="wizard.ticket-cost-and-boardings", message="wizard.stage.boarding-count.positive")
     * @Assert\Range(
     *      groups="wizard.ticket-cost-and-boardings",
     *      maxMessage="common.number.max",
     *      max=1000
     *  )
     */
    private ?int $boardingCount;

    /**
     * @ORM\Column(type="integer")
     */
    private ?int $number;

    /**
     * @ORM\PrePersist()
     */
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

    /**
     * @ORM\PreRemove()
     */
    public function setSiblingNumbersPreRemoval()
    {
        $journey = $this->journey;

        if (!$journey) {
            return;
        }

        // don't clear the Journey on this stage, because the DiaryKeeperStateSubscriber (changes the state of the DK
        // when they edit) needs to know the day number
        $journey->removeStage($this, false);
        $siblings = $journey->getStages()->toArray();

        usort($siblings, fn(Stage $a, Stage $b) => $a->getNumber() <=> $b->getNumber());

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
        $matchingStagesBeingAdded = $sourceJourney->getSharedToJourneysBeingAdded()->map(fn(Journey $j) => $j->getStageByNumber($this->getNumber()));
        return array_sum($matchingStagesBeingAdded->map(fn(Stage $s) => $s->getIsDriver() ? 1 : 0)->toArray());
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
