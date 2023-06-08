<?php

namespace App\Entity;

use App\Repository\InterviewerTrainingRecordRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=InterviewerTrainingRecordRepository::class)
 */
class InterviewerTrainingRecord
{
    public const STATE_NEW = 'new';
    public const STATE_IN_PROGRESS = 'in_progress';
    public const STATE_COMPLETE = 'complete';

    public const MODULE_PERSONAL_TRAVEL_DIARY = 'personal-diary';
    public const MODULE_WALKTHROUGH = 'walkthrough';
    public const MODULE_ONBOARDING = 'onboarding';
    public const MODULE_DIARY_CORRECTION = 'diary-correction';
    public const MODULE_DIARY_CORRECTION_ANSWERS = 'diary-correction-answers';

    public const MODULES = [
        self::MODULE_PERSONAL_TRAVEL_DIARY => 1,
        self::MODULE_WALKTHROUGH => 2,
        self::MODULE_ONBOARDING => 3,
        self::MODULE_DIARY_CORRECTION => 4,
        self::MODULE_DIARY_CORRECTION_ANSWERS => 5,
    ];

    public const MODULE_AREAS = [
        self::MODULE_PERSONAL_TRAVEL_DIARY => AreaPeriod::TRAINING_PERSONAL_DIARY_AREA_SERIAL,
        self::MODULE_ONBOARDING => AreaPeriod::TRAINING_ONBOARDING_AREA_SERIAL,
        self::MODULE_DIARY_CORRECTION => AreaPeriod::TRAINING_CORRECTION_AREA_SERIAL,
    ];

    use IdTrait;

    /**
     * @ORM\ManyToOne(targetEntity=Interviewer::class, inversedBy="trainingRecords")
     * @ORM\JoinColumn(nullable=false)
     */
    private ?Interviewer $interviewer;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private ?string $moduleName;

    /**
     * @ORM\OneToOne(targetEntity=Household::class, cascade={"persist", "remove"})
     */
    private ?Household $household;

    /**
     * @ORM\Column(type="datetime_immutable")
     */
    private ?\DateTimeImmutable $createdAt;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    private ?\DateTimeImmutable $startedAt;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    private ?\DateTimeImmutable $completedAt;

    public function getHousehold(): ?Household
    {
        return $this->household ?? null;
    }

    public function setHousehold(?Household $household): self
    {
        $this->household = $household;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt ?? null;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getStartedAt(): ?\DateTimeImmutable
    {
        return $this->startedAt ?? null;
    }

    public function setStartedAt(?\DateTimeImmutable $startedAt): self
    {
        $this->startedAt = $startedAt;

        return $this;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt ?? null;
    }

    public function setCompletedAt(?\DateTimeImmutable $completedAt): self
    {
        $this->completedAt = $completedAt;

        return $this;
    }

    public function getAreaPeriod(): ?AreaPeriod
    {
        return $this->getInterviewer()->getTrainingAreaPeriodBySerial(self::MODULE_AREAS[$this->getModuleName()] ?? '');
    }

    public function getState(): string
    {
        return match (true) {
            (!is_null($this->getCompletedAt())) => self::STATE_COMPLETE,
            (!is_null($this->getStartedAt())) => self::STATE_IN_PROGRESS,
            default => self::STATE_NEW,
        };
    }

    public function getInterviewer(): ?Interviewer
    {
        return $this->interviewer;
    }

    public function setInterviewer(?Interviewer $interviewer): self
    {
        $this->interviewer = $interviewer;

        return $this;
    }

    public function getModuleName(): ?string
    {
        return $this->moduleName;
    }

    public function setModuleName(string $moduleName): self
    {
        $this->moduleName = $moduleName;

        return $this;
    }

    public function getModuleNumber(): int
    {
        return self::MODULES[$this->getModuleName()];
    }
}
