<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\ApiResource;
use App\ApiPlatform\ApiIdInterface;
use App\ApiPlatform\Provider\InterviewerProvider;
use App\Attribute\ApiViolationMap;
use App\Attribute\DisableTrainingAreaPeriodFilter;
use App\Repository\InterviewerRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use App\Controller\Api\AreaAllocationController;
use App\Controller\Api\AreaDeallocationController;

#[ApiResource(
    operations: [
        new Get(
            provider: InterviewerProvider::class,
        ),
        new GetCollection(),
        new Post(),
        new Delete(
            provider: InterviewerProvider::class,
        ),
        new Post(uriTemplate: '/interviewers/{serialId}/allocate/{year}/{area}', controller: AreaAllocationController::class, read: false, deserialize: false),
        new Post(uriTemplate: '/interviewers/{serialId}/deallocate/{year}/{area}', controller: AreaDeallocationController::class, read: false, deserialize: false),
    ],
    validationContext: ['groups' => ['api.interviewer']],
    filters: ['api.interviewer.search_filter', 'api.interviewer.mapped_property_filter'],
)]
#[ApiViolationMap(['user.username' => 'email'])]
#[DisableTrainingAreaPeriodFilter]
#[UniqueEntity('serialId', groups: ['admin.interviewer', 'api.interviewer'])]
#[ORM\Entity(repositoryClass: InterviewerRepository::class)]
class Interviewer implements UserPersonInterface, ApiIdInterface
{

    use ApiIdTrait;

    #[ORM\ManyToMany(targetEntity: AreaPeriod::class, mappedBy: 'interviewers')]
    private Collection $areaPeriods;

    #[Assert\NotBlank(groups: ['admin.interviewer'])]
    #[Assert\NotBlank(message: 'api.interviewer.name.not-blank', groups: ['api.interviewer'])]
    #[ORM\Column(type: 'string', length: 255, nullable: false)]
    private ?string $name = null;

    #[Assert\Valid(groups: ['admin.interviewer', 'api.interviewer'])]
    #[ORM\OneToOne(targetEntity: User::class, mappedBy: 'interviewer', cascade: ['persist', 'remove'])]
    private ?User $user = null;

    #[ApiProperty(identifier: true)]
    #[Assert\NotBlank(groups: ['admin.interviewer'])]
    #[Assert\NotBlank(message: 'api.interviewer.serial-id.not-blank', groups: ['api.interviewer'])]
    #[ORM\Column(type: 'string', length: '10', unique: true, nullable: false)]
    private ?string $serialId = null;

    /**
     * @var Collection<int, AreaPeriod>
     */
    #[ORM\OneToMany(targetEntity: AreaPeriod::class, mappedBy: 'trainingInterviewer', orphanRemoval: true)]
    private Collection $trainingAreaPeriods;

    #[ORM\OneToMany(targetEntity: InterviewerTrainingRecord::class, mappedBy: 'interviewer', orphanRemoval: true)]
    #[ORM\OrderBy(['createdAt' => 'ASC'])]
    private Collection $trainingRecords;

    public function __construct()
    {
        $this->areaPeriods = new ArrayCollection();
        $this->trainingAreaPeriods = new ArrayCollection();
        $this->trainingRecords = new ArrayCollection();
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        // set (or unset) the owning side of the relation if necessary
        $newInterviewer = null === $user ? null : $this;
        if ($user->getInterviewer() !== $newInterviewer) {
            $user->setInterviewer($newInterviewer);
        }

        return $this;
    }

    /**
     * @return Collection<AreaPeriod>
     */
    public function getAreaPeriods(): Collection
    {
        return $this->areaPeriods;
    }

    public function addAreaPeriod(AreaPeriod $areaPeriod): self
    {
        if (!$this->areaPeriods->contains($areaPeriod)) {
            $this->areaPeriods[] = $areaPeriod;
            $areaPeriod->addInterviewer($this);
        }

        return $this;
    }

    public function removeAreaPeriod(AreaPeriod $areaPeriod): self
    {
        if ($this->areaPeriods->removeElement($areaPeriod)) {
            $areaPeriod->removeInterviewer($this);
        }

        return $this;
    }

    public function getSerialId(): ?string
    {
        return $this->serialId ?? null;
    }

    public function setSerialId(?string $serialId): self
    {
        $this->serialId = $serialId;

        return $this;
    }

    public function getApiId(): ?string
    {
        return $this->getSerialId();
    }

    /**
     * @return Collection<int, AreaPeriod>
     */
    public function getTrainingAreaPeriods(): Collection
    {
        return $this->trainingAreaPeriods;
    }

    public function getTrainingAreaPeriodBySerial(string $areaSerial): ?AreaPeriod
    {
        foreach ($this->trainingAreaPeriods as $areaPeriod)
        {
            if ($areaPeriod->getArea() === $areaSerial) {
                return $areaPeriod;
            }
        }
        return null;
    }

    public function hasTrainingRecordsForModule(string $module): bool
    {
        return !$this->getTrainingRecordsForModule($module)
            ->isEmpty();
    }

    /**
     * @return Collection<InterviewerTrainingRecord>
     */
    public function getTrainingRecordsForModule(string $module): Collection
    {
        return $this->getTrainingRecords()
            ->filter(fn(InterviewerTrainingRecord $trainingRecord): bool => $trainingRecord->getModuleName() === $module);
    }

    public function getLatestTrainingRecordForModule(string $module): ?InterviewerTrainingRecord
    {
        return $this->getTrainingRecordsForModule($module)->last() ?: null;
    }

    public function getTrainingPersonalDiaryKeeper(): DiaryKeeper
    {
        return $this
            ->getTrainingRecordsForModule(InterviewerTrainingRecord::MODULE_PERSONAL_TRAVEL_DIARY)->last()
            ->getHousehold()
            ->getDiaryKeepers()->first();
    }

    public function getTrainingHouseholdForCorrection(): Household
    {
        return $this
            ->getTrainingRecordsForModule(InterviewerTrainingRecord::MODULE_DIARY_CORRECTION)
            ->last()
            ->getHousehold();
    }

    public function addTrainingAreaPeriod(AreaPeriod $areaPeriod): self
    {
        if (!$this->trainingAreaPeriods->contains($areaPeriod)) {
            $this->trainingAreaPeriods[] = $areaPeriod;
            $areaPeriod->setTrainingInterviewer($this);
        }

        return $this;
    }

    public function removeTrainingAreaPeriod(AreaPeriod $areaPeriod): self
    {
        if ($this->trainingAreaPeriods->removeElement($areaPeriod)) {
            // set the owning side to null (unless already changed)
            if ($areaPeriod->getTrainingInterviewer() === $this) {
                $areaPeriod->setTrainingInterviewer(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, InterviewerTrainingRecord>
     */
    public function getTrainingRecords(): Collection
    {
        return $this->trainingRecords;
    }

    public function addTrainingRecord(InterviewerTrainingRecord $trainingRecord): self
    {
        if (!$this->trainingRecords->contains($trainingRecord)) {
            $this->trainingRecords[] = $trainingRecord;
            $trainingRecord->setInterviewer($this);
        }

        return $this;
    }

    public function removeTrainingRecord(InterviewerTrainingRecord $trainingRecord): self
    {
        if ($this->trainingRecords->removeElement($trainingRecord)) {
            // set the owning side to null (unless already changed)
            if ($trainingRecord->getInterviewer() === $this) {
                $trainingRecord->setInterviewer(null);
            }
        }

        return $this;
    }
}
