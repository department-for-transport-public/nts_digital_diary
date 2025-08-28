<?php

namespace App\Entity;

use App\Repository\InterviewerTrainingRecordRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InterviewerTrainingRecordRepository::class)]
class InterviewerTrainingRecord
{
    public const string STATE_NEW = 'new';
    public const string STATE_IN_PROGRESS = 'in_progress';
    public const string STATE_COMPLETE = 'complete';

    public const string TRANSITION_START = 'start';
    public const string TRANSITION_COMPLETE = 'complete';

    public const string MODULE_INTRODUCTION = 'introduction';
    public const string MODULE_PERSONAL_TRAVEL_DIARY = 'personal-diary';
    public const string MODULE_INTERVIEWER_DASHBOARD = 'interviewer-dashboard';
    public const string MODULE_MANUAL_ONBOARDING_INTRO = 'manual-onboarding-introduction';
    public const string MODULE_MANUAL_ONBOARDING_PRACTICE = 'manual-onboarding-practice';
    public const string MODULE_EDITING_DIARIES = 'editing-diaries';
    public const string MODULE_DIARY_CORRECTION = 'diary-correction';
    public const string MODULE_DIARY_CORRECTION_ANSWERS = 'diary-correction-answers';

    public const string MODULE_DIRECT_ONBOARDING_INTRO = 'direct-onboarding-introduction';
    public const string MODULE_APPROVE_AND_SUBMIT = 'approve-and-submit';

    public const array MODULES = [
        self::MODULE_INTRODUCTION => 1,
        self::MODULE_PERSONAL_TRAVEL_DIARY => 2,
        self::MODULE_MANUAL_ONBOARDING_PRACTICE => 3,
        self::MODULE_DIARY_CORRECTION => 4,
        self::MODULE_INTERVIEWER_DASHBOARD => 5,
        self::MODULE_DIRECT_ONBOARDING_INTRO => 6,
        self::MODULE_MANUAL_ONBOARDING_INTRO => 7,
        self::MODULE_EDITING_DIARIES => 8,
        self::MODULE_DIARY_CORRECTION_ANSWERS => 9,
        self::MODULE_APPROVE_AND_SUBMIT => 10,
    ];

    public const array MODULE_AREAS = [
        self::MODULE_PERSONAL_TRAVEL_DIARY => AreaPeriod::TRAINING_PERSONAL_DIARY_AREA_SERIAL,
        self::MODULE_MANUAL_ONBOARDING_PRACTICE => AreaPeriod::TRAINING_ONBOARDING_AREA_SERIAL,
        self::MODULE_DIARY_CORRECTION => AreaPeriod::TRAINING_CORRECTION_AREA_SERIAL,
    ];

    use IdTrait;

    #[ORM\ManyToOne(targetEntity: Interviewer::class, inversedBy: 'trainingRecords')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Interviewer $interviewer = null;

    #[ORM\Column(type: 'string', length: 50)]
    private ?string $moduleName = null;

    #[ORM\OneToOne(targetEntity: Household::class, cascade: ['persist', 'remove'])]
    private ?Household $household = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $startedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $completedAt = null;

    public function getHousehold(): ?Household
    {
        return $this->household ?? null;
    }

    public function setHousehold(?Household $household): self
    {
        $this->household = $household;

        return $this;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt ?? null;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getStartedAt(): ?DateTimeImmutable
    {
        return $this->startedAt ?? null;
    }

    public function setStartedAt(?DateTimeImmutable $startedAt): self
    {
        $this->startedAt = $startedAt;

        return $this;
    }

    public function getCompletedAt(): ?DateTimeImmutable
    {
        return $this->completedAt ?? null;
    }

    public function setCompletedAt(?DateTimeImmutable $completedAt): self
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

    public function setState(string $state): self
    {
        match ($state) {
            self::STATE_IN_PROGRESS => $this->startedAt ??= new DateTimeImmutable(),
            self::STATE_COMPLETE => $this->completedAt ??= new DateTimeImmutable(),
            default => $this,
        };
        return $this;
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
