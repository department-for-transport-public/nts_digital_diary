<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use App\ApiPlatform\Provider\HouseholdProvider;
use App\Repository\HouseholdRepository;
use App\Utility\TravelDiary\SerialHelper;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Ghost\GovUkCoreBundle\FormWizard\MultipleEntityInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ApiResource(
    operations: [
        new GetCollection(provider: HouseholdProvider::class),
    ],
)]
#[UniqueEntity(fields: ['addressNumber', 'householdNumber', 'areaPeriod'], message: 'wizard.on-boarding.household.unique-in-area-period', errorPath: '', groups: ['wizard.on-boarding.household', 'cli.add-household'])]
#[ORM\Entity(repositoryClass: HouseholdRepository::class)]
#[ORM\Table]
#[UniqueConstraint(columns: ['address_number', 'household_number', 'area_period_id'])]
class Household implements MultipleEntityInterface
{

    public const string STATE_APPROVED = 'approved';
    public const string STATE_COMPLETED = 'completed';
    public const string STATE_IN_PROGRESS = 'in_progress';
    public const string STATE_NEW = 'new';
    public const string STATE_SUBMITTED = 'submitted';

    use IdTrait;

    #[Assert\Range(notInRangeMessage: 'wizard.on-boarding.household.address-number.not-in-range', min: 1, max: AreaPeriod::HOUSEHOLDS_PER_AREA, groups: ['wizard.on-boarding.household', 'cli.add-household', 'direct-onboarding.ingress'])]
    #[Assert\NotBlank(message: 'wizard.on-boarding.household.address-number.not-blank', groups: ['wizard.on-boarding.household', 'cli.add-household', 'direct-onboarding.ingress'])]
    #[ORM\Column(type: 'smallint')]
    private ?int $addressNumber = null;

    #[Assert\Range(notInRangeMessage: 'wizard.on-boarding.household.household-number.not-in-range', min: 1, max: 99, groups: ['wizard.on-boarding.household', 'cli.add-household', 'direct-onboarding.ingress'])]
    #[Assert\NotBlank(message: 'wizard.on-boarding.household.household-number.not-blank', groups: ['wizard.on-boarding.household', 'cli.add-household', 'direct-onboarding.ingress'])]
    #[ORM\Column(type: 'smallint')]
    private ?int $householdNumber = null;

    #[Assert\NotBlank(message: 'wizard.on-boarding.household.start-date.not-blank', groups: ['wizard.on-boarding.household', 'admin.maintenance.edit-household', 'cli.add-household', 'direct-onboarding.ingress'])]
    #[Assert\Expression('!value or !this.getAreaPeriod() or value >= this.getAreaPeriod().getFirstValidDiaryStartDate()', message: 'wizard.on-boarding.household.start-date.too-early', groups: ['wizard.on-boarding.household', 'admin.maintenance.edit-household', 'cli.add-household', 'direct-onboarding.ingress'])]
    #[Assert\Expression('!value or !this.getAreaPeriod() or value <= this.getAreaPeriod().getLastValidDiaryStartDate()', message: 'wizard.on-boarding.household.start-date.too-late', groups: ['wizard.on-boarding.household', 'admin.maintenance.edit-household', 'cli.add-household', 'direct-onboarding.ingress'])]
    #[ORM\Column(type: 'date', nullable: true)]
    private ?DateTime $diaryWeekStartDate = null;

    /**
     * @var Collection<int, DiaryKeeper>
     */
    #[Assert\Count(
        min: 1,
        minMessage: 'wizard.on-boarding.submit.diary-keeper-count.at-least-one',
        groups: ['direct-onboarding.ingress', 'wizard.on-boarding.submit'],
    )]
    #[Assert\Valid(groups: ['direct-onboarding.ingress', 'direct-onboarding.logins', 'wizard.on-boarding.diary-keeper.media-type'])]
    #[Assert\Expression('this.getDiaryKeepersWithIdentifierForLogin().count() > 0', message: 'wizard.direct-onboarding.diary-keeper-logins.at-least-one', groups: ['direct-onboarding.logins'])]
    #[ORM\OneToMany(targetEntity: DiaryKeeper::class, mappedBy: 'household', orphanRemoval: true)]
    #[ORM\OrderBy(['number' => 'ASC'])]
    #[Assert\Unique(message: 'wizard.direct-onboarding.ingress.unique-diary-keeper-names', normalizer: [self::class, 'normalizeDiaryKeeperForDirectOnboardingIngressValidation'],  groups: ['direct-onboarding.ingress'], fields: ['name'])]
    private Collection $diaryKeepers;

    #[Assert\Valid(groups: ['direct-onboarding.ingress'])]
    #[ORM\OneToMany(targetEntity: Vehicle::class, mappedBy: 'household', orphanRemoval: true)]
    #[Assert\Unique(message: 'wizard.direct-onboarding.ingress.unique-vehicle-names', normalizer: [self::class, 'normalizeVehicleForDirectOnboardingIngressValidation'],  groups: ['direct-onboarding.ingress'], fields: ['friendlyName'])]
    private Collection $vehicles;

    #[Assert\NotNull(groups: ['direct-onboarding.ingress'])]
    #[ORM\ManyToOne(targetEntity: AreaPeriod::class, inversedBy: 'households')]
    #[ORM\JoinColumn(nullable: false)]
    private ?AreaPeriod $areaPeriod = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isOnboardingComplete = false;

    #[ORM\Column(type: 'boolean')]
    private bool $isJourneySharingEnabled = false;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $submittedBy = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?DateTime $submittedAt = null;

    #[Assert\Length(max: 1, maxMessage: 'wizard.on-boarding.household.check-letter.too-long', groups: ['wizard.on-boarding.check-letter-field', 'direct-onboarding.ingress'])]
    #[Assert\NotNull(message: 'wizard.on-boarding.household.check-letter.not-null', groups: ['wizard.on-boarding.check-letter-field', 'direct-onboarding.ingress'])]
    #[ORM\Column(type: 'string', length: 1, nullable: true)]
    private ?string $checkLetter = null;

    /**
     * The usual check for username uniqueness doesn't work during direct onboarding, as we are updating more than one
     * DK at a time, and the username might have been moved from one to another within the household. Combine this
     * with a special unique constraint excluding the household (see unique constraint on User) to ensure uniqueness
     * without blocking the special case of moving username from one user to another within the household.
     */
    #[Assert\Callback(groups: ['direct-onboarding.logins'])]
    public function checkDirectOnboardingUsernameUniqueness(ExecutionContextInterface $context): void
    {
        $usernames = [];
        foreach ($this->diaryKeepers as $idx=>$diaryKeeper) {
            $username = $diaryKeeper->getUser()->getUserIdentifier();
            if (!isset($usernames[$username])) $usernames[$username] = [];
            $usernames[$username][] = $idx;
        }
        $usernames = array_filter($usernames, fn(array $dkIdxs, $u): bool => count($dkIdxs) > 1, ARRAY_FILTER_USE_BOTH);

        foreach ($usernames as $idxs) {
            foreach ($idxs as $idx) {
                $context->buildViolation("wizard.direct-onboarding.diary-keeper-logins.email.already-used")
                    ->atPath("diaryKeepers[{$idx}].user.username")
                    ->addViolation();
            }
        }

    }

    #[Assert\Callback(groups: ['wizard.on-boarding.check-letter', 'direct-onboarding.ingress'])]
    public function checkCheckLetter(ExecutionContextInterface $context): void
    {
        if (!$this->areaPeriod instanceof AreaPeriod ||
            !$this->areaPeriod->getArea() ||
            $this->addressNumber === null ||
            $this->householdNumber === null ||
            $this->getCheckLetter() === null ||
            mb_strlen((string) $this->checkLetter) > 1
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
            ->filter(fn(DiaryKeeper $dk): ?bool => $dk->getIsAdult());
    }

    public function getDiaryKeepersWithIdentifierForLogin(): Collection
    {
        return $this
            ->diaryKeepers
            ->filter(fn(DiaryKeeper $dk): bool => $dk->hasIdentifierForLogin());
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
        if ($this->getIsSubmitted()) {
            return self::STATE_SUBMITTED;
        }

        [
            DiaryKeeper::STATE_NEW => $newCount,
            DiaryKeeper::STATE_IN_PROGRESS => $inProgressCount,
            DiaryKeeper::STATE_COMPLETED => $completedCount,
            DiaryKeeper::STATE_APPROVED => $approvedCount,
            DiaryKeeper::STATE_DISCARDED => $discardedCount,
        ] = $this->getDiaryKeeperStateCounts();
        $numDiaryKeepers = count($this->diaryKeepers);

        if ($numDiaryKeepers === $newCount) {
            return self::STATE_NEW;
        } else if ($numDiaryKeepers === $approvedCount + $discardedCount) {
            return self::STATE_APPROVED;
        } else if ($numDiaryKeepers === ($completedCount + $approvedCount + $discardedCount)) {
            return self::STATE_COMPLETED;
        } else {
            return self::STATE_IN_PROGRESS;
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
        return !is_null($this->submittedAt ?? null);
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

    public function getEntitiesToPersist(): array
    {
        return array_merge([$this], $this->diaryKeepers->toArray(), $this->vehicles->toArray());
    }

    public function isOverdue(): bool
    {
        $overdueDate = (new \DateTime())
            ->modify('-8 days'); // surveys last a week, we should give them at least a day extra

        return
            !$this->isTraining()
            && $this->getIsOnboardingComplete()
            && !$this->getIsSubmitted()
            && $this->getDiaryWeekStartDate() < $overdueDate;
    }

    public static function normalizeDiaryKeeperForDirectOnboardingIngressValidation(DiaryKeeper $diaryKeeper): array
    {
        return [
            'name' => $diaryKeeper->getName(),
            'number' => $diaryKeeper->getNumber(),
        ];
    }

    public static function normalizeVehicleForDirectOnboardingIngressValidation(Vehicle $vehicle): array
    {
        return [
            'friendlyName' => $vehicle->getFriendlyName(),
            'number' => $vehicle->getCapiNumber(),
        ];
    }

    public function isTraining(): bool
    {
        return $this->getAreaPeriod()->isTrainingArea();
    }
}
