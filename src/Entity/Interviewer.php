<?php

namespace App\Entity;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use App\ApiPlatform\Doctrine\Orm\Filter\MappedPropertySearchFilter;
use App\Repository\InterviewerRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use App\Annotation\ApiViolationMap;
use App\Controller\Api\AreaAllocationController;
use App\Controller\Api\AreaDeallocationController;
/**
 * @ORM\Entity (repositoryClass=InterviewerRepository::class)
 * @UniqueEntity ("serialId", groups={"admin.interviewer", "api.interviewer"})
 * @ApiViolationMap ({"user.username"="email"})
 */
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
        new Post(),
        new Delete(),
        new Post(uriTemplate: '/interviewers/{interviewer}/allocate/{areaPeriod}', controller: AreaAllocationController::class, read: false, deserialize: false),
        new Post(uriTemplate: '/interviewers/{interviewer}/deallocate/{areaPeriod}', controller: AreaDeallocationController::class, read: false, deserialize: false),
    ],
    validationContext: ['groups' => ['api.interviewer']],
    filters: ['api.interviewer.search_filter', 'api.interviewer.mapped_property_filter'],
)]
class Interviewer implements UserPersonInterface
{

    use IdTrait;

    /**
     * @ORM\ManyToMany(targetEntity=AreaPeriod::class, mappedBy="interviewers")
     */
    private Collection $areaPeriods;

    /**
     * @ORM\Column(type="string", length=255, nullable=false)
     * @Assert\NotBlank(groups={"admin.interviewer", "api.interviewer"})
     */
    private ?string $name = null;

    /**
     * @ORM\OneToOne(targetEntity=User::class, mappedBy="interviewer", cascade={"persist", "remove"})
     * @Assert\Valid(groups={"admin.interviewer", "api.interviewer"})
     */
    private ?User $user = null;

    /**
     * @ORM\Column(type="string", length="10", unique=true, nullable=false)
     * @Assert\NotBlank(groups={"admin.interviewer", "api.interviewer"})
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
