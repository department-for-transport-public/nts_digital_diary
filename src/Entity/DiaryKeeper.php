<?php

namespace App\Entity;

use App\Repository\DiaryKeeperRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use phpDocumentor\Reflection\Types\Boolean;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[UniqueEntity(fields: ['name', 'household'], message: 'wizard.diary-keeper.name.unique', groups: ['wizard.on-boarding.diary-keeper.details'])]
#[UniqueEntity(fields: ['number', 'household'], message: 'wizard.diary-keeper.number.unique', groups: ['wizard.on-boarding.diary-keeper.details'])]
#[ORM\Entity(repositoryClass: DiaryKeeperRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\Table]
#[ORM\UniqueConstraint(columns: ['name', 'household_id'])]
#[ORM\UniqueConstraint(columns: ['number', 'household_id'])]
class DiaryKeeper implements UserPersonInterface
{
    public const string STATE_NEW = 'new';
    public const string STATE_IN_PROGRESS = 'in-progress';
    public const string STATE_COMPLETED = 'completed';
    public const string STATE_APPROVED = 'approved';
    public const string STATE_DISCARDED = 'discarded';

    public const string TRANSITION_DISCARD = 'discard';
    public const string TRANSITION_APPROVE = 'approve';
    public const string TRANSITION_COMPLETE = 'complete';
    public const string TRANSITION_START = 'start';
    public const string TRANSITION_UNDO_APPROVAL = 'undo-approve';
    public const string TRANSITION_UNDO_DISCARD = 'undo-discard';
    public const string TRANSITION_UNDO_COMPLETE = 'undo-complete';

    public const string MEDIA_TYPE_DIGITAL = 'digital';
    public const string MEDIA_TYPE_PAPER = 'paper';
    public const string MEDIA_TYPE_INTERVIEWER_TRANSCRIBED = 'transcribe';
    use IdTrait;

    #[ORM\ManyToOne(targetEntity: Household::class, inversedBy: 'diaryKeepers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Household $household = null;

    #[Assert\NotNull(message: 'wizard.diary-keeper.is-adult.not-null', groups: ['wizard.on-boarding.diary-keeper.details', 'direct-onboarding.ingress'])]
    #[Assert\Expression('(value === true) || this.getPrimaryDriverVehicles().isEmpty()', groups: ['wizard.on-boarding.diary-keeper.details'], message: 'wizard.diary-keeper.is-adult.has-vehicles')]
    #[ORM\Column(type: 'boolean')]
    private ?bool $isAdult = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $hasUsedPracticeDay = null;

    #[Assert\NotBlank(message: 'wizard.diary-keeper.name.not-null', groups: ['wizard.on-boarding.diary-keeper.details', 'direct-onboarding.ingress'])]
    #[Assert\Length(max: 255, maxMessage: 'common.string.max-length', groups: ['wizard.on-boarding.diary-keeper.details', 'direct-onboarding.ingress'])]
    #[ORM\Column(type: 'string', length: 255)]
    private ?string $name = null;

    #[Assert\Range(notInRangeMessage: 'wizard.diary-keeper.number.not-in-range', min: 1, max: 99, groups: ['wizard.on-boarding.diary-keeper.details', 'direct-onboarding.ingress'])]
    #[Assert\NotBlank(message: 'wizard.diary-keeper.number.not-null', groups: ['wizard.on-boarding.diary-keeper.details', 'direct-onboarding.ingress'])]
    #[ORM\Column(type: 'integer')]
    private ?int $number = null;

    /**
     * @var Collection<int, DiaryDay>
     */
    #[ORM\OneToMany(mappedBy: 'diaryKeeper', targetEntity: DiaryDay::class, cascade: ['persist'], orphanRemoval: true, indexBy: 'number')]
    #[ORM\OrderBy(['number' => 'ASC'])]
    private Collection $diaryDays;

    /**
     * @var Collection<int, Vehicle>
     */
    #[ORM\OneToMany(mappedBy: 'primaryDriver', targetEntity: Vehicle::class)]
    #[ORM\OrderBy(['friendlyName' => 'ASC'])]
    private Collection $primaryDriverVehicles;

    private ?array $frequentLocations = [];

    /**
     * Orphan removal is required here, as part of on-boarding.
     */
    #[Assert\Valid(groups: ['wizard.on-boarding.diary-keeper.user-identifier', 'direct-onboarding.logins', 'wizard.on-boarding.direct.interviewer-training'])]
    #[ORM\OneToOne(mappedBy: 'diaryKeeper', targetEntity: User::class, cascade: ['persist'], orphanRemoval: true)]
    private ?User $user = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $diaryState;

    public const PROXY_VALIDATOR_EXPRESSION = "this.getUser().hasIdentifierForLogin() or this.hasProxies() or this.getHousehold().getDiaryKeepers().count() <= 1";

    #[Assert\Expression(expression: self::PROXY_VALIDATOR_EXPRESSION, message: 'wizard.on-boarding.diary-keeper.identity.at-least-one', groups: ['wizard.on-boarding.diary-keeper.user-identifier'])]
    #[Assert\Expression(expression: self::PROXY_VALIDATOR_EXPRESSION, message: 'change-proxies.at-least-one', groups: ['interviewer.diary-keeper.change-proxies'])]
    #[ORM\ManyToMany(targetEntity: DiaryKeeper::class, inversedBy: 'actingAsProxyFor')]
    #[ORM\JoinTable(name: 'diary_keeper_proxies')]
    private Collection $proxies;

    #[ORM\ManyToMany(targetEntity: DiaryKeeper::class, mappedBy: 'proxies')]
    private Collection $actingAsProxyFor;

    #[Assert\NotNull(message: 'common.choice.invalid', groups: ['wizard.on-boarding.diary-keeper.media-type'])]
//    #[Assert\Expression(expression: 'this.isValidMediaType()', message: 'common.choice.invalid', groups: ['wizard.on-boarding.diary-keeper.media-type'])]
    #[ORM\Column(type: 'string', length: 10)]
    private ?string $mediaType = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $approvalChecklistVerifiedBy = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $approvalChecklistVerifiedAt = null;

    #[ORM\OneToOne(targetEntity: SatisfactionSurvey::class, inversedBy: 'diaryKeeper', cascade: ['persist', 'remove'])]
    private ?SatisfactionSurvey $satisfactionSurvey = null;

    public function __construct()
    {
        $this->diaryDays = new ArrayCollection();
        // day 0 is the practice day
        // days 1-7 are the diary week
        foreach(range(0,7) as $number) {
            $day = (new DiaryDay())
                ->setNumber($number)
                ->setDiaryKeeper($this);

            $this->diaryDays->add($day);
        }

        $this->diaryState = self::STATE_NEW;
        $this->primaryDriverVehicles = new ArrayCollection();

        $this->proxies = new ArrayCollection();
        $this->actingAsProxyFor = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function checkNumber(): void
    {
        if (is_null($this->number)) {
            $highestNumber = 0;
            foreach($this->household->getDiaryKeepers() as $diaryKeeper) {
                $highestNumber = max($highestNumber, $diaryKeeper->getNumber());
            }
            $this->number = $highestNumber + 1;
        }
    }

    public function getHousehold(): ?Household
    {
        return $this->household ?? null;
    }

    public function setHousehold(?Household $household): self
    {
        $this->household = $household;
        // Set the training interviewer if there is one
        $this->getUser()?->setTrainingInterviewer($household?->getAreaPeriod()?->getTrainingInterviewer());
        return $this;
    }

    public function getIsAdult(): ?bool
    {
        return $this->isAdult ?? null;
    }

    public function setIsAdult(?bool $isAdult): self
    {
        $this->isAdult = $isAdult;
        return $this;
    }

    public function getHasUsedPracticeDay(): ?bool
    {
        return $this->hasUsedPracticeDay;
    }

    public function setHasUsedPracticeDay(?bool $hasUsedPracticeDay): static
    {
        $this->hasUsedPracticeDay = $hasUsedPracticeDay;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name ?? null;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;
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

    /**
     * @return Collection<int, DiaryDay>
     */
    public function getDiaryDays(): Collection
    {
        // don't include the practice day
        return $this->diaryDays->filter(fn(DiaryDay $d, $k): bool => $d->getNumber() !== 0);
    }

    public function getPracticeDay(): ?DiaryDay
    {
        return $this->getDiaryDayByNumber(0);
    }

    public function getDiaryDayByNumber(int $dayNumber): ?DiaryDay
    {
        return $this->diaryDays->get($dayNumber) ?? null;
    }

    public function addDiaryDay(DiaryDay $diaryDay): self
    {
        if (!$this->diaryDays->contains($diaryDay)) {
            $this->diaryDays[] = $diaryDay;
            $diaryDay->setDiaryKeeper($this);
        }

        return $this;
    }

    public function removeDiaryDay(DiaryDay $diaryDay): self
    {
        if ($this->diaryDays->contains($diaryDay)) {
            $this->diaryDays->removeElement($diaryDay);
            // set the owning side to null (unless already changed)
            if ($diaryDay->getDiaryKeeper() === $this) {
                $diaryDay->setDiaryKeeper(null);
            }
        }

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user ?? null;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        // set (or unset) the owning side of the relation if necessary
        $newDiaryKeeper = null === $user ? null : $this;
        if ($user && $user->getDiaryKeeper() !== $newDiaryKeeper) {
            $user->setDiaryKeeper($newDiaryKeeper);
        }

        return $this;
    }

    public function getFrequentLocations(): array
    {
        return $this->frequentLocations;
    }

    public function setFrequentLocations(array $frequentLocations): self
    {
        $this->frequentLocations = $frequentLocations;
        return $this;
    }

    /**
     * @return Collection<int, Vehicle>
     */
    public function getPrimaryDriverVehicles(): Collection
    {
        return $this->primaryDriverVehicles;
    }

    public function setPrimaryDriverVehicles(Collection $primaryDriverVehicles): self
    {
        $this->primaryDriverVehicles = $primaryDriverVehicles;
        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getProxies(): Collection
    {
        return $this->proxies;
    }

    public function addProxy(self $proxy): self
    {
        if (!$this->proxies->contains($proxy)) {
            $this->proxies[] = $proxy;
        }

        return $this;
    }

    public function removeProxy(self $proxy): self
    {
        $this->proxies->removeElement($proxy);

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getActingAsProxyFor(): Collection
    {
        return $this->actingAsProxyFor;
    }

    public function addActingAsProxyFor(self $actingAsProxyFor): self
    {
        if (!$this->actingAsProxyFor->contains($actingAsProxyFor)) {
            $this->actingAsProxyFor[] = $actingAsProxyFor;
            $actingAsProxyFor->addProxy($this);
        }

        return $this;
    }

    public function removeActingAsProxyFor(self $actingAsProxyFor): self
    {
        if ($this->actingAsProxyFor->removeElement($actingAsProxyFor)) {
            $actingAsProxyFor->removeProxy($this);
        }

        return $this;
    }

    public function getActingAsAProxyForNames(): array
    {
        return $this->actingAsProxyFor->map(fn(DiaryKeeper $dk): ?string => $dk->getName())->toArray();
    }

    public function isActingAsAProxyForOthers(): bool
    {
        return $this->actingAsProxyFor->count() > 0;
    }

    public function isActingAsProxyFor(DiaryKeeper $proxyTarget): bool
    {
        return $this->actingAsProxyFor->contains($proxyTarget);
    }

    public function hasProxies(): bool
    {
        return $this->proxies->count() > 0;
    }

    public function isProxiedBy(DiaryKeeper $proxy): bool
    {
        return $this->proxies->contains($proxy);
    }

    // -----

    public function isTheOnlyDiaryKeeper(): bool
    {
        $allDiaryKeepers = $this->getHousehold()->getDiaryKeepers();
        return $allDiaryKeepers->filter(fn(DiaryKeeper $d): bool => $d !== $this)->count() === 0;
    }

    public function getDiaryState(): ?string
    {
        return $this->diaryState;
    }

    public function setDiaryState(?string $diaryState): self
    {
        $this->diaryState = $diaryState;

        return $this;
    }

    // -----

    public function getProxyNames(): array {
        return $this->proxies->map(fn(DiaryKeeper $dk): ?string => $dk->getName())->toArray();
    }

    public function canBeDeletedWithoutMakingAnotherDiaryKeeperInaccessible(): bool
    {
        return count($this->whichDiaryKeepersWouldBeInaccessibleIfDeleted()) === 0;
    }

    public function whichDiaryKeepersWouldBeInaccessibleIfDeleted(): array
    {
        $inaccessible = [];

        foreach($this->getActingAsProxyFor() as $proxyTarget) {
            if (!$proxyTarget->hasIdentifierForLogin() && $proxyTarget->getProxies()->count() === 1) {
                $inaccessible[] = $proxyTarget;
            }
        }

        return $inaccessible;
    }

    public function hasIdentifierForLogin(): bool
    {
        $user = $this->getUser();
        return $user && $user->hasIdentifierForLogin();
    }

    public function hasJourneys(): bool
    {
        $x = array_map(fn(DiaryDay $dd) => $dd->getJourneys()->count(), $this->diaryDays->filter(fn(DiaryDay $d) => !$d->isPracticeDay())->toArray());
        return array_sum($x) > 0;
    }

    public function getMediaType(): ?string
    {
        return $this->mediaType ?? null;
    }

    public function setMediaType(?string $mediaType): self
    {
        $this->mediaType = $mediaType;

        return $this;
    }

    public function getApprovalChecklistVerifiedBy(): ?string
    {
        return $this->getDiaryState() === self::STATE_APPROVED
            ? $this->approvalChecklistVerifiedBy
            : null;
    }

    public function setApprovalChecklistVerifiedBy(?string $approvalChecklistVerifiedBy): self
    {
        $this->approvalChecklistVerifiedBy = $approvalChecklistVerifiedBy;

        return $this;
    }

    public function getApprovalChecklistVerifiedAt(): ?DateTimeImmutable
    {
        return $this->getDiaryState() === self::STATE_APPROVED
            ? $this->approvalChecklistVerifiedAt
            : null;
    }

    public function setApprovalChecklistVerifiedAt(?DateTimeImmutable $approvalChecklistVerifiedAt): self
    {
        $this->approvalChecklistVerifiedAt = $approvalChecklistVerifiedAt;

        return $this;
    }

    public function hasEmptyDays(): bool
    {
        return !$this
            ->getDiaryDays()
            ->filter(fn(DiaryDay $d) => $d->getJourneys()->isEmpty())
            ->isEmpty();
    }

    public function getSatisfactionSurvey(): ?SatisfactionSurvey
    {
        return $this->satisfactionSurvey;
    }

    public function setSatisfactionSurvey(?SatisfactionSurvey $satisfactionSurvey): self
    {
        $this->satisfactionSurvey = $satisfactionSurvey;
        return $this;
    }
    public function getSerialNumber(bool $padAddressPart=true, bool $spacesBetweenParts=true): string
    {
        return $this->household->getSerialNumber($this->getNumber(), $padAddressPart, $spacesBetweenParts);
    }

    public function isValidMediaType(): bool
    {
        return in_array($this->getMediaType(), [self::MEDIA_TYPE_DIGITAL, self::MEDIA_TYPE_INTERVIEWER_TRANSCRIBED, self::MEDIA_TYPE_PAPER]);
    }
}
