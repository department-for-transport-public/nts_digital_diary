<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\ApiPlatform\ApiIdInterface;
use App\ApiPlatform\Provider\AreaPeriodProvider;
use App\Repository\AreaPeriodRepository;
use App\Utility\TravelDiary\AreaPeriodHelper;
use DateInterval;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Ignore;
use Symfony\Component\Validator\Constraints as Assert;
use UnexpectedValueException;

/**
 * @ORM\Entity(repositoryClass=AreaPeriodRepository::class)
 * @ORM\Table(uniqueConstraints={@UniqueConstraint(columns={"area", "year", "training_interviewer_id"})})
 * @UniqueEntity(errorPath="area", fields={"area","year"}, groups={"area.import", "api.area-period"}, message="admin.area.unique")
 */
#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/area_periods/{year}/{area}.{_format}',
            provider: AreaPeriodProvider::class,
        ),
        new GetCollection(),
        new Post(),
        new Delete(
            uriTemplate: '/area_periods/{year}/{area}.{_format}',
            security: 'is_granted("DELETE", object)',
            provider: AreaPeriodProvider::class,
        ),
    ],
    validationContext: ['groups' => ['api.area-period']],
)]
class AreaPeriod implements ApiIdInterface
{
    use ApiIdTrait;

    const TRAINING_PERSONAL_DIARY_AREA_SERIAL = "000000";
    const TRAINING_ONBOARDING_AREA_SERIAL = "000100";
    const TRAINING_CORRECTION_AREA_SERIAL = "000200";

    /**
     * @ORM\Column(type="string", length=6, nullable=false)
     * @Assert\NotBlank(groups={"interviewer.allocate-area", "api.area-period"}, message="api.area-period.area.not-blank")
     * @Assert\Regex("/^\d{1}((0[1-9])|(1[0-2]))\d{3}$/", groups={"interviewer.allocate-area", "api.area-period"}, message="api.area-period.area.valid")
     */
    #[ApiProperty(identifier: true)]
    private ?string $area = null;

    /**
     * @ORM\Column(type="integer", nullable=false)
     * @Assert\NotBlank(groups={"api.area-period"}, message="api.area-period.year.not-blank")
     * @Assert\Expression("(value % 10) == this.getYearDigitFromArea()", groups={"api.area-period"}, message="api.area-period.year.expression")
     */
    #[ApiProperty(identifier: true)]
    private ?int $year;

    /**
     * @ORM\Column(type="integer", nullable=false)
     * @Assert\NotBlank(groups={"api.area-period"}, message="api.area-period.month.not-blank")
     * @Assert\Expression("value == this.getMonthDigitsFromArea()", groups={"api.area-period"}, message="api.area-period.month.expression")
     */
    private ?int $month;

    /**
     * @ORM\OneToMany(targetEntity=Household::class, mappedBy="areaPeriod", orphanRemoval=true)
     * @Ignore
     * @var Collection<Household>
     */
    private Collection $households;

    /**
     * @ORM\ManyToMany(targetEntity=Interviewer::class, inversedBy="areaPeriods")
     * @Ignore
     */
    private Collection $interviewers;

    /**
     * @ORM\OneToMany(targetEntity=OtpUser::class, mappedBy="areaPeriod", cascade={"remove"})
     * @Ignore
     */
    private Collection $otpUsers;

    /**
     * @ORM\ManyToOne(targetEntity=Interviewer::class, inversedBy="trainingAreaPeriods")
     * @Ignore
     */
    private ?Interviewer $trainingInterviewer;

    public function __construct()
    {
        $this->households = new ArrayCollection();
        $this->interviewers = new ArrayCollection();
        $this->otpUsers = new ArrayCollection();
    }

    public function getArea(): ?string
    {
        return $this->area;
    }

    public function setArea(?string $area): self
    {
        $this->area = $area;
        return $this;
    }

    /**
     * @return Collection<Household>
     */
    public function getHouseholds(): Collection
    {
        return $this->households;
    }

    public function addHousehold(Household $household): self
    {
        if (!$this->households->contains($household)) {
            $this->households[] = $household;
            $household->setAreaPeriod($this);
        }

        return $this;
    }

    public function removeHousehold(Household $household): self
    {
        if ($this->households->removeElement($household)) {
            // set the owning side to null (unless already changed)
            if ($household->getAreaPeriod() === $this) {
                $household->setAreaPeriod(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<Interviewer>
     */
    public function getInterviewers(): Collection
    {
        if ($this->getTrainingInterviewer()) {
            $interviewers = new ArrayCollection();
            $interviewers->add($this->getTrainingInterviewer());
            return $interviewers;
        }
        return $this->interviewers;
    }

    public function addInterviewer(Interviewer $interviewer): self
    {
        if (!$this->interviewers->contains($interviewer)) {
            $this->interviewers[] = $interviewer;
        }

        return $this;
    }

    public function removeInterviewer(Interviewer $interviewer): self
    {
        $this->interviewers->removeElement($interviewer);

        return $this;
    }

    /**
     * @return Collection<OtpUser>
     */
    public function getOtpUsers(): Collection
    {
        return $this->otpUsers;
    }

    public function addOtpUser(OtpUser $otpUser): self
    {
        if (!$this->otpUsers->contains($otpUser)) {
            $this->otpUsers[] = $otpUser;
            $otpUser->setAreaPeriod($this);
        }

        return $this;
    }

    public function removeOtpUser(OtpUser $otpUser): self
    {
        if ($this->otpUsers->removeElement($otpUser)) {
            // set the owning side to null (unless already changed)
            if ($otpUser->getAreaPeriod() === $this) {
                $otpUser->setAreaPeriod(null);
            }
        }

        return $this;
    }

    public function getYear(): ?int
    {
        return $this->year;
    }

    public function setYear(?int $year): self
    {
        $this->year = $year;
        return $this;
    }

    public function getMonth(): ?int
    {
        return $this->month;
    }

    public function setMonth(?int $month): self
    {
        $this->month = $month;
        return $this;
    }

    public function populateMonthAndYearFromArea(): self
    {
        if (preg_match('/^(?<year>\d)(?<month>\d{2})(?<sample>\d{3})$/', $this->area, $matches)) {
            $this->year = AreaPeriodHelper::guessYearFromArea($this->area);
            $this->month = $matches['month'];
        }

        return $this;
    }

    public function getYearDigitFromArea(): ?string
    {
        return $this->area ? substr($this->area, 0, 1) : null;
    }

    public function getMonthDigitsFromArea(): ?string
    {
        return $this->area ? substr($this->area, 1, 2) : null;
    }

    public function getFirstValidDiaryStartDate(): DateTime
    {
        return $this->getTrainingInterviewer()
            ? (new DateTime())->modify("midnight, first day of last month")
            : new DateTime("{$this->year}/{$this->month}/01 midnight");
    }

    public function getLastValidDiaryStartDate(): DateTime
    {
        return $this->getFirstValidDiaryStartDate()
            ->add(DateInterval::createFromDateString('3 months'))
            ->sub(DateInterval::createFromDateString('1 second'));
    }

    public function getApiId(): ?string
    {
        return "{$this->getYear()}/{$this->getArea()}";
    }

    public function getTrainingInterviewer(): ?Interviewer
    {
        return $this->trainingInterviewer ?? null;
    }

    public function setTrainingInterviewer(?Interviewer $trainingInterviewer): self
    {
        $this->trainingInterviewer = $trainingInterviewer;

        return $this;
    }

    public function getLatestTrainingRecord(): InterviewerTrainingRecord
    {
        $module = array_search($this->area, InterviewerTrainingRecord::MODULE_AREAS);
        if ($module === false) {
            throw new UnexpectedValueException("Cannot find training module for {$this->area}");
        }
        return $this->trainingInterviewer->getLatestTrainingRecordForModule($module);
    }

    /**
     * @return array{onboarded: int, submitted: int, non-submitted: int}
     */
    public function getHouseholdSubmittedCounts(): array
    {
        $stateCounts = [
            'onboarded' => 0,
            'submitted' => 0,
            'total' => 0,
        ];

        foreach($this->households as $household) {
            $stateCounts['onboarded'] += $household->getIsOnboardingComplete() ? 1 : 0;
            $stateCounts['submitted'] += $household->getIsSubmitted() ? 1 : 0;
            $stateCounts['total'] += 1;
        }

        return $stateCounts;
    }

    public function isArchived(): bool
    {
        // N.B. Logic matches that in (Interviewer) DashboardController
        return $this->getLastValidDiaryStartDate() < (new \DateTime())->sub(new \DateInterval('P9W'));
    }
}
