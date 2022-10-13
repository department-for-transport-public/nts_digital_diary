<?php

namespace App\Entity\Journey;

use App\Entity\BasicMetadata;
use App\Entity\BasicMetadataTrait;
use App\Entity\DiaryDay;
use App\Entity\DiaryKeeper;
use App\Entity\IdTrait;
use App\Entity\PropertyChangeLoggable;
use App\Repository\Journey\JourneyRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use RuntimeException;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @ORM\Entity(repositoryClass=JourneyRepository::class)
 */
class Journey implements PropertyChangeLoggable, BasicMetadata
{
    use IdTrait;
    use BasicMetadataTrait;

    const TO_GO_HOME = 'Go home';

    const MERGE_PROPERTIES = [
                                'diaryDay',
        /* purpose form */      'purpose',
        /* times form */        'startTime', 'endTime',
        /* locations form */    'startLocation', 'isStartHome', 'endLocation', 'isEndHome',
    ];

    const SHARE_JOURNEY_CLONE_PROPERTIES = [
        'diaryDay',
        /* times form */        'startTime', 'endTime',
        /* locations form */    'startLocation', 'isStartHome', 'endLocation', 'isEndHome',
    ];

    /**
     * @ORM\ManyToOne(targetEntity=DiaryDay::class, inversedBy="journeys")
     * @ORM\JoinColumn(nullable=false)
     * @Assert\NotNull(message="wizard.return-journey.diary-day.not-null", groups={"wizard.return-journey.target-day"})
     * @Assert\NotNull(message="wizard.repeat-journey.target-day.not-null", groups={"wizard.repeat-journey.target-day"})
     */
    private ?DiaryDay $diaryDay;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\NotNull(groups={"wizard.journey.purpose"}, message="wizard.journey.purpose.not-null")
     * @Assert\Length(
     *     groups={"wizard.journey.purpose"},
     *     maxMessage="common.string.max-length", max=255
     * )
     */
    private ?string $purpose;

    /**
     * @Assert\Callback(groups="wizard.share-journey.purposes")
     */
    public function validateSharingPurpose(ExecutionContextInterface $context)
    {
        foreach ($this->sharedTo as $value) {
            $context->getValidator()->validate($value, []);
            if (is_null($value->getPurpose())) {
                $context->buildViolation('wizard.share-journey.purposes.not-null', [
                    'name' => $value->getDiaryDay()->getDiaryKeeper()->getName(),
                ])
                    ->atPath("purpose-{$value->getDiaryDay()->getDiaryKeeper()->getId()}")
                    ->addViolation();
            }
            else if (strlen($value->getPurpose()) > 255) {
                $context->buildViolation('common.string.max-length', ['limit' => 255])
                    ->atPath("purpose-{$value->getDiaryDay()->getDiaryKeeper()->getId()}")
                    ->addViolation();
            }
        }
    }

    /**
     * @ORM\Column(type="time")
     * @Assert\NotNull(
     *     groups={"wizard.journey.times", "wizard.repeat-journey.journey-times", "wizard.return-journey.journey-times"},
     *     message="wizard.journey.start-time.not-null"
     * )
     */
    private ?DateTime $startTime;

    /**
     * @ORM\Column(type="time", nullable=true)
     * @Assert\NotNull(
     *     groups={"wizard.journey.times", "wizard.repeat-journey.journey-times", "wizard.return-journey.journey-times"},
     *     message="wizard.journey.end-time.not-null"
     * )
     */
    private ?DateTime $endTime;

    /**
     * @var Collection|Stage[]
     * @ORM\OneToMany(targetEntity=Stage::class, mappedBy="journey", orphanRemoval=true, cascade={"persist"})
     * @ORM\OrderBy({"number"="ASC"})
     */
    private Collection $stages;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\NotNull(groups={"wizard.journey.locations.start-other"}, message="wizard.journey.start-other.not-null")
     * @Assert\Length(groups={"wizard.journey.locations.start-general"}, maxMessage="common.string.max-length", max=255)
     */
    private ?string $startLocation;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\NotNull(groups={"wizard.journey.locations.end-other"}, message="wizard.journey.end-other.not-null")
     * @Assert\Length(groups={"wizard.journey.locations.end-general"}, maxMessage="common.string.max-length", max=255)
     */
    private ?string $endLocation;

    /**
     * @ORM\Column(type="boolean")
     */
    private ?bool $isStartHome;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private ?bool $isEndHome;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $isPartial = true;

    /**
     * @ORM\ManyToOne(targetEntity=Journey::class, inversedBy="sharedTo")
     */
    private ?Journey $sharedFrom;

    /**
     * @ORM\OneToMany(targetEntity=Journey::class, mappedBy="sharedFrom", cascade={"persist"})
     * @Assert\Count(min=1, minMessage="wizard.share-journey.who-with.not-blank", groups="wizard.share-journey.share-to")
     * @var Collection | Journey[]
     */
    private Collection $sharedTo;

    /**
     * @Assert\Callback(groups={"wizard.share-journey.share-to"})
     */
    public function validateSharedToCount(ExecutionContextInterface $context) {
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
     * @return Collection|Stage[]
     */
    public function getStages()
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

    public function getIsPartial(): ?bool
    {
        return $this->isPartial;
    }

    public function setIsPartial(bool $isPartial): self
    {
        $this->isPartial = $isPartial;
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
        $peopleCounts = $this->stages->map(fn(Stage $s) => $s->getTravellingPeopleCount())->toArray();
        return empty($peopleCounts) ? null : min($peopleCounts);
    }

    public function getSharedToNames(): ?string
    {
        if (empty($this->sharedTo)) {
            return null;
        }

        $names = $this->sharedTo->map(fn(Journey $j) => $j->getDiaryDay()->getDiaryKeeper()->getName());
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
     * @return array | Journey[]
     */
    public function getSharedToJourneysBeingAdded(): array
    {
        /** @var Journey[] $sharedJourneysBeingAdded */
        $sharedJourneysBeingAdded = $this->getSharedTo()->filter(fn(Journey $j) => $j->getId() === null);

        $data = [];
        foreach($sharedJourneysBeingAdded as $journey) {
            $diaryKeeper = $journey->getDiaryDay()->getDiaryKeeper();
            $data[$diaryKeeper->getId()] = $journey;
        };

        return $data;
    }
}
