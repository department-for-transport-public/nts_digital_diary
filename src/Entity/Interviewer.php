<?php

namespace App\Entity;

use App\Repository\InterviewerRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=InterviewerRepository::class)
 * @UniqueEntity("serialId", groups={"admin.interviewer"})
 */
class Interviewer implements UserPersonInterface
{
    use IdTrait;

    /**
     * @ORM\ManyToMany(targetEntity=AreaPeriod::class, mappedBy="interviewers")
     */
    private Collection $areaPeriods;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotNull(groups="admin.interviewer")
     */
    private ?string $name = null;

    /**
     * @ORM\OneToOne(targetEntity=User::class, mappedBy="interviewer", cascade={"persist"})
     * @Assert\Valid(groups={"admin.interviewer"})
     */
    private ?User $user = null;

    /**
     * @ORM\Column(type="string", length="10", unique=true)
     * @Assert\NotNull(groups="admin.interviewer")
     */
    private ?string $serialId;

    public function __construct()
    {
        $this->areaPeriods = new ArrayCollection();
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
     * @return Collection|AreaPeriod[]
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
        return $this->serialId;
    }

    public function setSerialId(?string $serialId): self
    {
        $this->serialId = $serialId;

        return $this;
    }
}
