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

/**
 * @ORM\Entity(repositoryClass="App\Repository\HouseholdRepository", repositoryClass=HouseholdRepository::class)
 * @UniqueEntity(groups={"wizard.on-boarding.household"}, errorPath="", message="wizard.on-boarding.household.unique-in-area-period", fields={"addressNumber", "householdNumber", "areaPeriod"})
 */
class Household
{
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
     */
    private ?DateTime $diaryWeekStartDate;

    /**
     * @var DiaryKeeper[] | Collection
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
     * @Assert\Length(groups={"wizard.on-boarding.check-letter"}, maxMessage="wizard.on-boarding.household.check-letter.too-long", max=1)
     * @Assert\NotNull(groups={"wizard.on-boarding.check-letter"}, message="wizard.on-boarding.household.check-letter.not-null")
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
     * @return Collection|DiaryKeeper[]
     */
    public function getDiaryKeepers()
    {
        return $this->diaryKeepers;
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

    public function getSerialNumber(?int $diaryKeeperNumber = null): string
    {
        $addressPart = str_pad($this->getAddressNumber(), 2, '0', STR_PAD_LEFT);
        $diaryKeeperPart = $diaryKeeperNumber ? " / $diaryKeeperNumber" : '';

        return "{$this->getAreaPeriod()->getArea()} / {$addressPart} / {$this->getHouseholdNumber()}{$diaryKeeperPart}";
    }

    public function getAreaPeriod(): ?AreaPeriod
    {
        return $this->areaPeriod;
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
            return self::STATE_SUBMITTED;
        }

        $stateCounts = [
            DiaryKeeper::STATE_NEW => 0,
            DiaryKeeper::STATE_IN_PROGRESS => 0,
            DiaryKeeper::STATE_COMPLETED => 0,
            DiaryKeeper::STATE_APPROVED => 0,
        ];

        foreach($this->diaryKeepers as $diaryKeeper) {
            $stateCounts[$diaryKeeper->getDiaryState() ?? DiaryKeeper::STATE_NEW]++;
        }

        $numDiaryKeepers = count($this->diaryKeepers);

        if ($numDiaryKeepers === $stateCounts[DiaryKeeper::STATE_APPROVED]) {
            return DiaryKeeper::STATE_APPROVED;
        } else if ($numDiaryKeepers === $stateCounts[DiaryKeeper::STATE_NEW]) {
            return DiaryKeeper::STATE_NEW;
        } else if ($numDiaryKeepers === ($stateCounts[DiaryKeeper::STATE_COMPLETED] + $stateCounts[DiaryKeeper::STATE_APPROVED])) {
            return DiaryKeeper::STATE_COMPLETED;
        } else {
            return DiaryKeeper::STATE_IN_PROGRESS;
        }
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
        $this->checkLetter = $checkLetter;
        return $this;
    }
}
