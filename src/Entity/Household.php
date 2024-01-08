<?php

namespace App\Entity;

use App\Repository\HouseholdRepository;
use App\Utility\TravelDiary\SerialHelper;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Doctrine\ORM\Mapping\UniqueConstraint;
use function PHPUnit\Framework\isEmpty;

/**
 * @ORM\Entity(repositoryClass="App\Repository\HouseholdRepository", repositoryClass=HouseholdRepository::class)
 * @ORM\Table(uniqueConstraints={@UniqueConstraint(columns={"address_number", "household_number", "area_period_id"})})
 * @UniqueEntity(groups={"wizard.on-boarding.household"}, errorPath="", message="wizard.on-boarding.household.unique-in-area-period", fields={"addressNumber", "householdNumber", "areaPeriod"})
 */
class Household
{
    const STATE_APPROVED = 'approved';
    const STATE_COMPLETED = 'completed';
    const STATE_IN_PROGRESS = 'in-progress';
    const STATE_NEW = 'new';
    const STATE_SUBMITTED = 'submitted';

    use IdTrait;

    /**
     * @ORM\Column(type="smallint")
     * @Assert\Range(min=1, max=99, groups={"wizard.on-boarding.household"}, notInRangeMessage="wizard.on-boarding.household.address-number.not-in-range")
     * @Assert\NotBlank(groups={"wizard.on-boarding.household"}, message="wizard.on-boarding.household.address-number.not-blank")
     */
    private ?int $addressNumber;

    /**
     * @ORM\Column(type="smallint")
     * @Assert\Range(min=1, max=99, groups={"wizard.on-boarding.household"}, notInRangeMessage="wizard.on-boarding.household.household-number.not-in-range")
     * @Assert\NotBlank(groups={"wizard.on-boarding.household"}, message="wizard.on-boarding.household.household-number.not-blank")
     */
    private ?int $householdNumber;

    /**
     * @ORM\Column(type="date", nullable=true)
     * @Assert\NotBlank(groups={"wizard.on-boarding.household"}, message="wizard.on-boarding.household.start-date.not-blank")
     * @Assert\Expression(groups={"wizard.on-boarding.household"},"!value or value >= this.getAreaPeriod().getFirstValidDiaryStartDate()", message="wizard.on-boarding.household.start-date.too-early")
     * @Assert\Expression(groups={"wizard.on-boarding.household"},"!value or value <= this.getAreaPeriod().getLastValidDiaryStartDate()", message="wizard.on-boarding.household.start-date.too-late")
     */
    private ?DateTime $diaryWeekStartDate;

    /**
     * @var Collection<int, DiaryKeeper>
     * @ORM\OneToMany(targetEntity=DiaryKeeper::class, mappedBy="household", orphanRemoval=true)
     * @ORM\OrderBy({"number": "ASC"})
     */
    private Collection $diaryKeepers;

    /**
     * @ORM\OneToMany(targetEntity=Vehicle::class, mappedBy="household", orphanRemoval=true)
     */
    private Collection $vehicles;

    /**
     * @ORM\ManyToOne(targetEntity=AreaPeriod::class, inversedBy="households")
     * @ORM\JoinColumn(nullable=false)
     */
    private ?AreaPeriod $areaPeriod;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $isOnboardingComplete = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $isJourneySharingEnabled = false;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $submittedBy;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?DateTime $submittedAt;

    /**
     * @ORM\Column(type="string", length=1, nullable=true)
     * @Assert\Length(groups={"wizard.on-boarding.check-letter-field"}, maxMessage="wizard.on-boarding.household.check-letter.too-long", max=1)
     * @Assert\NotNull(groups={"wizard.on-boarding.check-letter-field"}, message="wizard.on-boarding.household.check-letter.not-null")
     */
    private ?string $checkLetter;

    /**
     * @Assert\Callback(groups={"wizard.on-boarding.check-letter"})
     */
    public function checkCheckLetter(ExecutionContextInterface $context): void
    {
        if (!$this->areaPeriod instanceof AreaPeriod ||
            !$this->areaPeriod->getArea() ||
            $this->addressNumber === null ||
            $this->householdNumber === null ||
            $this->getCheckLetter() === null ||
            mb_strlen($this->checkLetter) > 1
        ) {
            return;
        }

        if ($this->checkLetter !== SerialHelper::getCheckLetter($this->areaPeriod->getArea(), $this->addressNumber, $this->householdNumber)) {
            $context
                ->buildViolation("wizard.on-boarding.household.check-letter.invalid")
                ->atPath("checkLetter")
                ->addViolation();
        }
    }

    public function __construct()
    {
        $this->diaryKeepers = new ArrayCollection();
        $this->vehicles = new ArrayCollection();
    }

    public function getDiaryWeekStartDate(): ?DateTime
    {
        return $this->diaryWeekStartDate ?? null;
    }

    public function setDiaryWeekStartDate(?DateTime $diaryWeekStartDate): self
    {
        $this->diaryWeekStartDate = $diaryWeekStartDate;
        return $this;
    }

    public function getDiaryKeeperByNumber(int $number): ?DiaryKeeper
    {
        foreach ($this->diaryKeepers as $diaryKeeper) {
            if ($diaryKeeper->getNumber() === $number) {
                return $diaryKeeper;
            }
        }
        return null;
    }

    /**
     * @return Collection<int, DiaryKeeper>
     */
    public function getDiaryKeepers(): Collection
    {
        return $this->diaryKeepers;
    }

    /**
     * @return Collection<int, DiaryKeeper>
     */
    public function getDiaryKeepersWhoCanBePrimaryDrivers(): Collection
    {
        return $this
            ->diaryKeepers
            ->filter(fn(DiaryKeeper $dk) => $dk->getIsAdult());
    }

    public function addDiaryKeeper(DiaryKeeper $diaryKeeper): self
    {
        if (!$this->diaryKeepers->contains($diaryKeeper)) {
            $this->diaryKeepers[] = $diaryKeeper;
            $diaryKeeper->setHousehold($this);
        }

        return $this;
    }

    public function removeDiaryKeeper(DiaryKeeper $diaryKeeper): self
    {
        if ($this->diaryKeepers->contains($diaryKeeper)) {
            $this->diaryKeepers->removeElement($diaryKeeper);
            // set the owning side to null (unless already changed)
            if ($diaryKeeper->getHousehold() === $this) {
                $diaryKeeper->setHousehold(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Vehicle[]
     */
    public function getVehicles(): Collection
    {
        return $this->vehicles;
    }

    public function addVehicle(Vehicle $vehicle): self
    {
        if (!$this->vehicles->contains($vehicle)) {
            $this->vehicles[] = $vehicle;
            $vehicle->setHousehold($this);
        }

        return $this;
    }

    public function removeVehicle(Vehicle $vehicle): self
    {
        if ($this->vehicles->contains($vehicle)) {
            $this->vehicles->removeElement($vehicle);
            // set the owning side to null (unless already changed)
            if ($vehicle->getHousehold() === $this) {
                $vehicle->setHousehold(null);
            }
        }

        return $this;
    }

    public function getAddressNumber(): ?int
    {
        return $this->addressNumber ?? null;
    }

    public function setAddressNumber(?int $addressNumber): self
    {
        $this->addressNumber = $addressNumber;
        return $this;
    }

    public function getHouseholdNumber(): ?int
    {
        return $this->householdNumber ?? null;
    }

    public function setHouseholdNumber(?int $householdNumber): self
    {
        $this->householdNumber = $householdNumber;
        return $this;
    }

    public function getSerialNumber(?int $diaryKeeperNumber = null, bool $padAddressPart=true, bool $spacesBetweenParts=true): string
    {
        $addressPart = $this->getAddressNumber();

        if ($padAddressPart) {
            $addressPart = str_pad($addressPart, 2, '0', STR_PAD_LEFT);
        }

        $separator = $spacesBetweenParts ? ' / ' : '/';
        $diaryKeeperPart = $diaryKeeperNumber ? "{$separator}{$diaryKeeperNumber}" : '';

        return "{$this->getAreaPeriod()->getArea()}{$separator}{$addressPart}{$separator}{$this->getHouseholdNumber()}{$diaryKeeperPart}";
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

    public function getIsOnboardingComplete(): bool
    {
        return $this->isOnboardingComplete;
    }

    public function setIsOnboardingComplete(bool $isOnboardingComplete): self
    {
        $this->isOnboardingComplete = $isOnboardingComplete;
        return $this;
    }

    public function isJourneySharingEnabled(): bool
    {
        return $this->isJourneySharingEnabled;
    }

    public function setIsJourneySharingEnabled(bool $isJourneySharingEnabled): Household
    {
        $this->isJourneySharingEnabled = $isJourneySharingEnabled;
        return $this;
    }

    // -----

    public function getState(): string
    {
        if ($this->submittedAt !== null) {
            return Household::STATE_SUBMITTED;
        }

        [
            DiaryKeeper::STATE_NEW => $newCount,
            DiaryKeeper::STATE_IN_PROGRESS => $inProgressCount,
            DiaryKeeper::STATE_COMPLETED => $completedCount,
            DiaryKeeper::STATE_APPROVED => $approvedCount,
            DiaryKeeper::STATE_DISCARDED => $discardedCount,
        ] = $this->getDiaryKeeperStateCounts();
        $numDiaryKeepers = count($this->diaryKeepers);

        if ($numDiaryKeepers === $approvedCount + $discardedCount) {
            return Household::STATE_APPROVED;
        } else if ($numDiaryKeepers === $newCount) {
            return Household::STATE_NEW;
        } else if ($numDiaryKeepers === ($completedCount + $approvedCount + $discardedCount)) {
            return Household::STATE_COMPLETED;
        } else {
            return Household::STATE_IN_PROGRESS;
        }
    }

    /**
     * @return array<string, int>
     */
    public function getDiaryKeeperStateCounts(): array
    {
        $stateCounts = [
            DiaryKeeper::STATE_NEW => 0,
            DiaryKeeper::STATE_IN_PROGRESS => 0,
            DiaryKeeper::STATE_COMPLETED => 0,
            DiaryKeeper::STATE_APPROVED => 0,
            DiaryKeeper::STATE_DISCARDED => 0,
        ];

        foreach($this->diaryKeepers as $diaryKeeper) {
            $stateCounts[$diaryKeeper->getDiaryState() ?? DiaryKeeper::STATE_NEW]++;
        }

        return $stateCounts;
    }

    public function getSubmittedBy(): ?string
    {
        return $this->submittedBy;
    }

    public function setSubmittedBy(?string $submittedBy): self
    {
        $this->submittedBy = $submittedBy;

        return $this;
    }

    public function getSubmittedAt(): ?\DateTime
    {
        return $this->submittedAt;
    }

    public function setSubmittedAt(?\DateTime $submittedAt): self
    {
        $this->submittedAt = $submittedAt;

        return $this;
    }

    public function getIsSubmitted(): bool
    {
        return !is_null($this->submittedAt);
    }

    public function getCheckLetter(): ?string
    {
        return $this->checkLetter ?? null;
    }

    public function setCheckLetter(?string $checkLetter): self
    {
        $this->checkLetter = null !== $checkLetter ? strtoupper($checkLetter): null;
        return $this;
    }
}
