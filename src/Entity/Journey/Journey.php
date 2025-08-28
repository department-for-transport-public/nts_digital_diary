<?php

namespace App\Entity\Journey;

use App\Entity\BasicMetadataInterface;
use App\Entity\BasicMetadataTrait;
use App\Entity\DiaryDay;
use App\Entity\DiaryKeeper;
use App\Entity\IdTrait;
use App\Entity\PropertyChangeLoggableInterface;
use App\Repository\Journey\JourneyRepository;
use App\Validator\JourneySharingValidator;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use RuntimeException;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[Assert\Callback([JourneySharingValidator::class, 'validateJourney'], groups: ['wizard.share-journey.purpose'])]
#[ORM\Entity(repositoryClass: JourneyRepository::class)]
class Journey implements PropertyChangeLoggableInterface, BasicMetadataInterface
{
    use IdTrait;
    use BasicMetadataTrait;

    public const TO_GO_HOME = 'Go home';

    public const MERGE_PROPERTIES = [
                                'diaryDay',
        /* purpose form */      'purpose',
        /* times form */        'startTime', 'endTime',
        /* locations form */    'startLocation', 'isStartHome', 'endLocation', 'isEndHome',
    ];

    public const SHARE_JOURNEY_CLONE_PROPERTIES = [
        /* times form */        'startTime', 'endTime',
        /* locations form */    'startLocation', 'isStartHome', 'endLocation', 'isEndHome',
    ];

    #[Assert\NotNull(message: 'wizard.return-journey.diary-day.not-null', groups: ['wizard.return-journey.target-day'])]
    #[Assert\NotNull(message: 'wizard.repeat-journey.target-day.not-null', groups: ['wizard.repeat-journey.target-day'])]
    #[ORM\ManyToOne(targetEntity: DiaryDay::class, inversedBy: 'journeys')]
    #[ORM\JoinColumn(nullable: false)]
    private ?DiaryDay $diaryDay = null;

    #[Assert\NotNull(message: 'wizard.journey.purpose.not-null', groups: ['wizard.journey.purpose', 'wizard.split-journey.purpose'])]
    #[Assert\NotNull(message: 'wizard.share-journey.purpose.not-null', groups: ['wizard.share-journey.purpose.entry'])]
    #[Assert\Length(max: 255, maxMessage: 'common.string.max-length', groups: ['wizard.journey.purpose', 'wizard.share-journey.purpose.entry', 'wizard.split-journey.purpose'])]
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $purpose = null;

    #[Assert\NotNull(message: 'wizard.journey.start-time.not-null', groups: ['wizard.journey.times', 'wizard.repeat-journey.journey-times', 'wizard.return-journey.journey-times'])]
    #[ORM\Column(type: 'time')]
    private ?DateTime $startTime = null;

    #[Assert\NotNull(message: 'wizard.journey.end-time.not-null', groups: ['wizard.journey.times', 'wizard.repeat-journey.journey-times', 'wizard.return-journey.journey-times'])]
    #[ORM\Column(type: 'time', nullable: true)]
    private ?DateTime $endTime = null;

    /**
     * @var Collection<Stage>
     */
    #[ORM\OneToMany(mappedBy: 'journey', targetEntity: Stage::class, cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['number' => 'ASC'])]
    private Collection $stages;

    #[Assert\NotNull(message: 'wizard.journey.start-other.not-null', groups: ['wizard.journey.locations.start-other'])]
    #[Assert\Length(max: 255, maxMessage: 'common.string.max-length', groups: ['wizard.journey.locations.start-general'])]
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $startLocation = null;

    #[Assert\NotNull(message: 'wizard.journey.end-other.not-null', groups: ['wizard.journey.locations.end-other'])]
    #[Assert\NotNull(message: 'wizard.split-journey.midpoint-other.not-null', groups: ['wizard.split-journey.midpoint-other'])]
    #[Assert\Length(max: 255, maxMessage: 'common.string.max-length', groups: ['wizard.journey.locations.end-general', 'wizard.split-journey.midpoint-other'])]
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $endLocation = null;

    #[ORM\Column(type: 'boolean')]
    private ?bool $isStartHome = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $isEndHome = null;

    #[ORM\ManyToOne(targetEntity: Journey::class, inversedBy: 'sharedTo')]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?Journey $sharedFrom = null;

    /**
     * @var Collection<Journey>
     */
    #[Assert\Count(min: 1, minMessage: 'wizard.share-journey.who-with.not-blank', groups: ['wizard.share-journey.share-to'])]
    #[ORM\OneToMany(mappedBy: 'sharedFrom', targetEntity: Journey::class, cascade: ['persist'])]
    private Collection $sharedTo;

    #[Assert\Callback(groups: ['wizard.share-journey.share-to'])] // NOTE (2023-05-03): while re-writing journey sharing tests, realised that this doesn't validate
    public function validateSharedToCount(ExecutionContextInterface $context): void {
        if ($this->sharedTo->count() >= $this->getMinimumStageTravellerCount()) {
            $context->buildViolation('wizard.share-journey.share-to.count', [
                'minStageTravellerCount' => $this->getMinimumStageTravellerCount(),
                'max' => $this->getMinimumStageTravellerCount() - 1,
                'count' => $this->sharedTo->count(),
            ])
                ->atPath('shareTo')
                ->addViolation();
        }
    }

    public function __construct()
    {
        $this->stages = new ArrayCollection();
        $this->sharedTo = new ArrayCollection();
    }

    public function getDiaryDay(): ?DiaryDay
    {
        return $this->diaryDay ?? null;
    }

    public function setDiaryDay(?DiaryDay $diaryDay): self
    {
        $this->diaryDay = $diaryDay;
        return $this;
    }

    public function getPurpose(): ?string
    {
        return $this->purpose ?? null;
    }

    public function setPurpose(?string $purpose): self
    {
        $this->purpose = $purpose;
        return $this;
    }

    public function getStartTime(): ?DateTime
    {
        return $this->startTime ?? null;
    }

    public function setStartTime(?DateTime $startTime): self
    {
        $this->startTime = $startTime;
        return $this;
    }

    public function getEndTime(): ?DateTime
    {
        return $this->endTime ?? null;
    }

    public function setEndTime(?DateTime $endTime): self
    {
        $this->endTime = $endTime;
        return $this;
    }

    /**
     * @return Collection<Stage>
     */
    public function getStages(): Collection
    {
        return $this->stages;
    }

    public function getStageByNumber(int $number): Stage
    {
        foreach ($this->getStages() as $stage) {
            if ($stage->getNumber() === $number) {
                return $stage;
            }
        }
        throw new RuntimeException("Could not find stage with number: $number");
    }

    public function addStage(Stage $stage): self
    {
        if (!$this->stages->contains($stage)) {
            $this->stages[] = $stage;
            $stage->setJourney($this);
        }

        return $this;
    }

    public function removeStage(Stage $stage, bool $clearJourney = true): self
    {
        if ($this->stages->contains($stage)) {
            $this->stages->removeElement($stage);
            // set the owning side to null (unless already changed)
            if ($stage->getJourney() === $this && $clearJourney) {
                $stage->setJourney(null);
            }
        }

        return $this;
    }

    public function getStartLocation(): ?string
    {
        return $this->startLocation ?? null;
    }

    public function setStartLocation(?string $startLocation): self
    {
        $this->startLocation = $startLocation;
        return $this;
    }

    public function getEndLocation(): ?string
    {
        return $this->endLocation ?? null;
    }

    public function setEndLocation(?string $endLocation): self
    {
        $this->endLocation = $endLocation;
        return $this;
    }

    public function getIsStartHome(): ?bool
    {
        return $this->isStartHome ?? null;
    }

    public function setIsStartHome(?bool $isStartHome): self
    {
        $this->isStartHome = $isStartHome;
        return $this;
    }

    public function getIsEndHome(): ?bool
    {
        return $this->isEndHome ?? null;
    }

    public function setIsEndHome(?bool $isEndHome): self
    {
        $this->isEndHome = $isEndHome;
        return $this;
    }

    public function getSharedFrom(): ?self
    {
        return $this->sharedFrom ?? null;
    }

    public function setSharedFrom(?self $sharedFrom): self
    {
        $this->sharedFrom = $sharedFrom;
        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getSharedTo(): Collection
    {
        return $this->sharedTo;
    }

    public function addSharedTo(self $sharedTo): self
    {
        if (!$this->isSharedWithDiaryKeeper($sharedTo->getDiaryDay()->getDiaryKeeper())) {
            $this->sharedTo[] = $sharedTo;
            $sharedTo->setSharedFrom($this);
        }

        return $this;
    }

    public function removeSharedTo(self $sharedTo): self
    {
        if ($this->sharedTo->removeElement($sharedTo)) {
            // set the owning side to null (unless already changed)
            if ($sharedTo->getSharedFrom() === $this) {
                $sharedTo->setSharedFrom(null);
            }
        }

        return $this;
    }

    public function removeSharedWithDiaryKeeper(DiaryKeeper $diaryKeeper): self
    {
        $diaryKeeperId = $diaryKeeper->getId();
        foreach($this->getSharedTo() as $sharedJourney) {
            if ($sharedJourney->getDiaryDay()->getDiaryKeeper()->getId() === $diaryKeeperId) {
                $this->removeSharedTo($sharedJourney);
            }
        }
        return $this;
    }

    // -----

    public function getStartLocationForDisplay(): string
    {
        return $this->getIsStartHome() ? 'Home' : ($this->startLocation ?? '');
    }

    public function getEndLocationForDisplay(): string
    {
        return $this->getIsEndHome() ? 'Home' : ($this->endLocation ?? '');
    }

    public function isGoingHome(): ?bool
    {
        return $this->getIsEndHome() && !$this->getIsStartHome();
    }

    public function getMinimumStageTravellerCount(): ?int
    {
        $peopleCounts = $this->stages->map(fn(Stage $s): int => $s->getTravellingPeopleCount())->toArray();
        return empty($peopleCounts) ? null : min($peopleCounts);
    }

    public function getSharedToNames(): ?string
    {
        if (empty($this->sharedTo)) {
            return null;
        }

        $names = $this->sharedTo->map(fn(Journey $j): ?string => $j->getDiaryDay()->getDiaryKeeper()->getName());
        return join(', ', $names->toArray());
    }

    public function isSharedWithDiaryKeeper(DiaryKeeper $diaryKeeper): bool
    {
        $diaryKeeperId = $diaryKeeper->getId();
        foreach($this->getSharedTo() as $sharedJourney) {
            if ($sharedJourney->getDiaryDay()->getDiaryKeeper()->getId() === $diaryKeeperId) {
                return true;
            }
        }
        return false;
    }

    public function isShared(): bool
    {
        return !$this->getSharedTo()->isEmpty();
    }

    public function wasCreatedBySharing(): bool
    {
        return $this->getSharedFrom() !== null;
    }

    /**
     * @return Collection<int, Journey>
     */
    public function getSharedToJourneysBeingAdded(): Collection
    {
        return $this->getSharedTo()->filter(fn(Journey $j): bool => $j->getId() === null);
    }
}
