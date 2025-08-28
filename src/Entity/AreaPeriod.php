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
            // using security to prevent deleting area with households, as couldn't get validation to work in delete context
            security: 'is_granted("DELETE", object)',
            provider: AreaPeriodProvider::class,
        ),
    ],
    validationContext: ['groups' => ['api.area-period']],
)]
#[UniqueEntity(fields: ['area', 'year'], message: 'admin.area.unique', errorPath: 'area', groups: ['area.import', 'api.area-period'])]
#[ORM\Entity(repositoryClass: AreaPeriodRepository::class)]
#[ORM\Table]
#[UniqueConstraint(columns: ['area', 'year', 'training_interviewer_id'])]
class AreaPeriod implements ApiIdInterface
{
    use ApiIdTrait;

    public const int VALID_FIELDWORK_PERIOD_MONTHS = 3;

    public const TRAINING_PERSONAL_DIARY_AREA_SERIAL = "000000";
    public const TRAINING_ONBOARDING_AREA_SERIAL = "000100";
    public const TRAINING_CORRECTION_AREA_SERIAL = "000200";

    public const AREA_PATTERN = "\d{1}((0[1-9])|(1[0-2]))\d{3}";
    public const AREA_REGEX = "/^" . self::AREA_PATTERN . "$/";

    #[ApiProperty(identifier: true)]
    #[Assert\NotBlank(message: 'api.area-period.area.not-blank', groups: ['interviewer.allocate-area', 'api.area-period', 'cli.add-area-period'])]
    #[Assert\Regex(self::AREA_REGEX, message: 'api.area-period.area.valid', groups: ['interviewer.allocate-area', 'api.area-period', 'cli.add-area-period'])]
    #[ORM\Column(type: 'string', length: 6, nullable: false)]
    private ?string $area = null;

    #[ApiProperty(identifier: true)]
    #[Assert\NotBlank(message: 'api.area-period.year.not-blank', groups: ['api.area-period', 'cli.add-area-period'])]
    #[Assert\Expression('(value % 10) == this.getYearDigitFromArea()', message: 'api.area-period.year.expression', groups: ['api.area-period', 'cli.add-area-period'])]
    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $year = null;

    #[Assert\NotBlank(message: 'api.area-period.month.not-blank', groups: ['api.area-period', 'cli.add-area-period'])]
    #[Assert\Expression('value == this.getMonthDigitsFromArea()', message: 'api.area-period.month.expression', groups: ['api.area-period', 'cli.add-area-period'])]
    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $month = null;

    /**
     * @var Collection<Household>
     */
    #[Ignore]
    #[ORM\OneToMany(targetEntity: Household::class, mappedBy: 'areaPeriod')]
    private Collection $households;

    #[Ignore]
    #[ORM\ManyToMany(targetEntity: Interviewer::class, inversedBy: 'areaPeriods')]
    private Collection $interviewers;

    #[Ignore]
    #[ORM\OneToMany(targetEntity: OtpUser::class, mappedBy: 'areaPeriod', cascade: ['remove'])]
    private Collection $otpUsers;

    #[Ignore]
    #[ORM\ManyToOne(targetEntity: Interviewer::class, inversedBy: 'trainingAreaPeriods')]
    private ?Interviewer $trainingInterviewer = null;

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
        if (preg_match('/^(?<year>\d)(?<month>\d{2})(?<sample>\d{3})$/', (string) $this->area, $matches)) {
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
            ? new DateTime("midnight, first day of last month")
            : new DateTime("{$this->year}/{$this->month}/01 midnight");
    }

    public function getLastValidDiaryStartDate(): DateTime
    {
        return $this->getFirstValidDiaryStartDate()
            ->add(DateInterval::createFromDateString(self::VALID_FIELDWORK_PERIOD_MONTHS . ' months'))
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
