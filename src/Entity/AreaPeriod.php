<?php

namespace App\Entity;

use App\Repository\AreaPeriodRepository;
use App\Utility\AreaPeriodHelper;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=AreaPeriodRepository::class)
 * @UniqueEntity(errorPath="area", fields={"area","year"}, groups={"area.import", "api.area-period"}, message="admin.area.unique")
 */
class AreaPeriod
{
    use IdTrait;

    /**
     * @ORM\Column(type="integer", nullable=false)
     * @Assert\NotBlank(groups={"interviewer.allocate-area", "api.area-period"}, message="allocate-area.area.not-blank")
     * @Assert\Regex("/^\d{1}((0[1-9])|(1[0-2]))\d{3}$/", groups={"interviewer.allocate-area", "api.area-period"}, message="allocate-area.area.valid")
     */
    private ?int $area = null;

    /**
     * @ORM\Column(type="integer", nullable=false)
     */
    private ?int $year;

    /**
     * @ORM\Column(type="integer", nullable=false)
     */
    private ?int $month;

    /**
     * @ORM\OneToMany(targetEntity=Household::class, mappedBy="areaPeriod")
     */
    private Collection $households;

    /**
     * @ORM\ManyToMany(targetEntity=Interviewer::class, inversedBy="areaPeriods")
     */
    private Collection $interviewers;

    /**
     * @ORM\OneToMany(targetEntity=OtpUser::class, mappedBy="areaPeriod")
     */
    private Collection $otpUsers;

    public function __construct()
    {
        $this->households = new ArrayCollection();
        $this->interviewers = new ArrayCollection();
        $this->otpUsers = new ArrayCollection();
    }

    public function getArea(): ?int
    {
        return $this->area;
    }

    public function setArea(?int $area): self
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
}
