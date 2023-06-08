<?php

namespace App\Entity;

use App\Repository\DiaryKeeperRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=DiaryKeeperRepository::class)
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(uniqueConstraints={
 *     @ORM\UniqueConstraint(columns={"name", "household_id"}),
 *     @ORM\UniqueConstraint(columns={"number", "household_id"})
 * })
 * @UniqueEntity(groups={"wizard.on-boarding.diary-keeper.details"}, message="wizard.diary-keeper.name.unique", fields={"name", "household"})
 * @UniqueEntity(groups={"wizard.on-boarding.diary-keeper.details"}, message="wizard.diary-keeper.number.unique", fields={"number", "household"})
 */
class DiaryKeeper implements UserPersonInterface
{
    const STATE_NEW = 'new';
    const STATE_IN_PROGRESS = 'in-progress';
    const STATE_COMPLETED = 'completed';
    const STATE_APPROVED = 'approved';
    const STATE_DISCARDED = 'discarded';

    const TRANSITION_DISCARD = 'discard';
    const TRANSITION_APPROVE = 'approve';
    const TRANSITION_COMPLETE = 'complete';
    const TRANSITION_START = 'start';
    const TRANSITION_UNDO_APPROVAL = 'undo-approve';
    const TRANSITION_UNDO_DISCARD = 'undo-discard';
    const TRANSITION_UNDO_COMPLETE = 'undo-complete';

    const MEDIA_TYPE_DIGITAL = 'digital';
    const MEDIA_TYPE_PAPER = 'paper';
    use IdTrait;

    /**
     * @ORM\ManyToOne(targetEntity=Household::class, inversedBy="diaryKeepers")
     * @ORM\JoinColumn(nullable=false)
     */
    private ?Household $household;

    /**
     * @ORM\Column(type="boolean")
     * @Assert\NotNull(groups={"wizard.on-boarding.diary-keeper.details"}, message="wizard.diary-keeper.is-adult.not-null")
     * @Assert\Expression("(value === true) || this.getPrimaryDriverVehicles().isEmpty()", groups={"wizard.on-boarding.diary-keeper.details"}, message="wizard.diary-keeper.is-adult.has-vehicles")
     */
    private ?bool $isAdult;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank(groups={"wizard.on-boarding.diary-keeper.details"}, message="wizard.diary-keeper.name.not-null")
     * @Assert\Length(groups={"wizard.on-boarding.diary-keeper.details"}, maxMessage="common.string.max-length", max=255)
     */
    private ?string $name;

    /**
     * @ORM\Column(type="integer")
     * @Assert\Range(min=1, max=99, groups={"wizard.on-boarding.diary-keeper.details"}, notInRangeMessage="wizard.diary-keeper.number.not-in-range")
     * @Assert\NotBlank(groups={"wizard.on-boarding.diary-keeper.details"}, message="wizard.diary-keeper.number.not-null")
     */
    private ?int $number;

    /**
     * @var Collection<int, DiaryDay>
     * @ORM\OneToMany(targetEntity=DiaryDay::class, mappedBy="diaryKeeper", cascade={"persist"}, indexBy="number", orphanRemoval=true)
     * @ORM\OrderBy({"number" = "ASC"})
     */
    private Collection $diaryDays;

    /**
     * @var Collection<int, Vehicle>
     * @ORM\OneToMany(targetEntity=Vehicle::class, mappedBy="primaryDriver")
     * @ORM\OrderBy({"friendlyName" = "ASC"})
     */
    private Collection $primaryDriverVehicles;

    private ?array $frequentLocations = [];

    /**
     * Orphan removal is required here, as part of on-boarding.
     * @ORM\OneToOne(targetEntity=User::class, mappedBy="diaryKeeper", cascade={"persist"}, orphanRemoval=true)
     * @Assert\Valid(groups={"wizard.on-boarding.diary-keeper.user-identifier"})
     */
    private ?User $user;

    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     */
    private ?string $diaryState;

    /**
     * @ORM\ManyToMany(targetEntity=DiaryKeeper::class, inversedBy="actingAsProxyFor")
     * @ORM\JoinTable(name="diary_keeper_proxies")
     */
    private Collection $proxies;

    /**
     * @ORM\ManyToMany(targetEntity=DiaryKeeper::class, mappedBy="proxies")
     */
    private Collection $actingAsProxyFor;

    /**
     * @ORM\Column(type="string", length=10)
     * @Assert\NotNull(groups={"wizard.on-boarding.diary-keeper.media-type"}, message="common.choice.invalid")
     */
    private ?string $mediaType;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $emptyDaysVerifiedBy;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    private ?DateTimeImmutable $emptyDaysVerifiedAt;

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

    /**
     * @ORM\PrePersist()
     */
    public function checkNumber()
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
        return $this->diaryDays->filter(fn(DiaryDay $d, $k) => $d->getNumber() !== 0);
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
        return $this->actingAsProxyFor->map(fn(DiaryKeeper $dk) => $dk->getName())->toArray();
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
        return $allDiaryKeepers->filter(fn(DiaryKeeper $d) => $d !== $this)->count() === 0;
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
        return $this->proxies->map(fn(DiaryKeeper $dk) => $dk->getName())->toArray();
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

    public function getMediaType(): ?string
    {
        return $this->mediaType ?? null;
    }

    public function setMediaType(?string $mediaType): self
    {
        $this->mediaType = $mediaType;

        return $this;
    }

    public function getEmptyDaysVerifiedBy(): ?string
    {
        return $this->getDiaryState() === self::STATE_APPROVED
            ? $this->emptyDaysVerifiedBy
            : null;
    }

    public function setEmptyDaysVerifiedBy(?string $emptyDaysVerifiedBy): self
    {
        $this->emptyDaysVerifiedBy = $emptyDaysVerifiedBy;

        return $this;
    }

    public function getEmptyDaysVerifiedAt(): ?DateTimeImmutable
    {
        return $this->getDiaryState() === self::STATE_APPROVED
            ? $this->emptyDaysVerifiedAt
            : null;
    }

    public function setEmptyDaysVerifiedAt(?DateTimeImmutable $emptyDaysVerifiedAt): self
    {
        $this->emptyDaysVerifiedAt = $emptyDaysVerifiedAt;

        return $this;
    }
}
