<?php

namespace App\Entity;

use App\Entity\Journey\Journey;
use App\Repository\DiaryDayRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: DiaryDayRepository::class)]
class DiaryDay
{
    public const int PRACTICE_DAY_NUMBER = 0;
    public const int EXAMPLE_DAY_NUMBER = -1;

    use IdTrait;

    #[ORM\Column(type: 'integer')]
    private ?int $number = null;

    #[Assert\Length(max: 4000, maxMessage: 'common.string.max-length', groups: ['diary-keeper.notes'])]
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $diaryKeeperNotes = null;

    #[Assert\Length(max: 4000, maxMessage: 'common.string.max-length', groups: ['interviewer.notes'])]
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $interviewerNotes = null;

    #[Assert\Length(max: 4000, maxMessage: 'common.string.max-length', groups: ['coder.notes'])]
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $coderNotes = null;

    #[ORM\ManyToOne(targetEntity: DiaryKeeper::class, inversedBy: 'diaryDays')]
    #[ORM\JoinColumn(nullable: false)]
    private ?DiaryKeeper $diaryKeeper = null;

    /**
     * @var Collection|Journey[]
     */
    #[ORM\OneToMany(mappedBy: 'diaryDay', targetEntity: Journey::class, orphanRemoval: true)]
    #[ORM\OrderBy(['startTime' => 'ASC'])]
    private Collection $journeys;

    public function __construct()
    {
        $this->journeys = new ArrayCollection();
    }

    public function getNumber(): ?int
    {
        return $this->number;
    }

    public function setNumber(int $number): self
    {
        $this->number = $number;
        return $this;
    }

    public function getDiaryKeeperNotes(): ?string
    {
        return $this->diaryKeeperNotes;
    }

    public function setDiaryKeeperNotes(?string $diaryKeeperNotes): self
    {
        $this->diaryKeeperNotes = $diaryKeeperNotes;
        return $this;
    }

    public function getDiaryKeeper(): ?DiaryKeeper
    {
        return $this->diaryKeeper;
    }

    public function setDiaryKeeper(?DiaryKeeper $diaryKeeper): self
    {
        $this->diaryKeeper = $diaryKeeper;
        return $this;
    }

    /**
     * @return Collection<Journey>
     */
    public function getJourneys(): Collection
    {
        return $this->journeys;
    }

    public function getNonEmptyJourneys(): Collection
    {
        return $this->journeys->filter(fn(Journey $journey) => $journey->getStages()->count() > 0);
    }

    public function addJourney(Journey $journey): self
    {
        if (!$this->journeys->contains($journey)) {
            $this->journeys[] = $journey;
            $journey->setDiaryDay($this);
        }

        return $this;
    }

    public function removeJourney(Journey $journey): self
    {
        if ($this->journeys->contains($journey)) {
            $this->journeys->removeElement($journey);
            // set the owning side to null (unless already changed)
            if ($journey->getDiaryDay() === $this) {
                $journey->setDiaryDay(null);
            }
        }

        return $this;
    }

    public function getInterviewerNotes(): ?string
    {
        return $this->interviewerNotes;
    }

    public function setInterviewerNotes(?string $interviewerNotes): self
    {
        $this->interviewerNotes = $interviewerNotes;
        return $this;
    }

    public function getCoderNotes(): ?string
    {
        return $this->coderNotes;
    }

    public function setCoderNotes(?string $coderNotes): self
    {
        $this->coderNotes = $coderNotes;
        return $this;
    }

    // -----

    public function getDate(): \DateTime
    {
        $date = clone $this->diaryKeeper->getHousehold()->getDiaryWeekStartDate();
        $dayInterval = $this->number - 1;
        if ($dayInterval !== 0) {
            // sprintf used to force the printing of + sign when positive
            $date->modify(sprintf("%+d days", $dayInterval));
        }
        return $date;
    }

    public function isPracticeDay(): bool
    {
        return $this->number === self::PRACTICE_DAY_NUMBER;
    }

    public function hasShortWalks(): bool
    {
        foreach ($this->journeys as $journey) {
            if ($journey->hasShortWalkStage()) {
                return true;
            }
        }
        return false;
    }

    public function isExampleDay(): bool
    {
        return $this->number === self::EXAMPLE_DAY_NUMBER;
    }
}
