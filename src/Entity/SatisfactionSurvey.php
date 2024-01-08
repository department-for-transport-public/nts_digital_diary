<?php

namespace App\Entity;

use App\Repository\SatisfactionSurveyRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @ORM\Entity(repositoryClass=SatisfactionSurveyRepository::class)
 */
class SatisfactionSurvey
{
    use IdTrait;

    /**
     * @ORM\Column(type="string", length=32)
     * @Assert\NotBlank(groups={"wizard.satisfaction-survey.ease-of-use"}, message="wizard.satisfaction-survey.ease-of-use.not-blank")
     */
    private ?string $easeRating = null;

    /**
     * @ORM\Column(type="string", length=32)
     * @Assert\NotBlank(groups={"wizard.satisfaction-survey.burden"}, message="wizard.satisfaction-survey.burden-rating.not-blank")
     */
    private ?string $burdenRating = null;


    /**
     * @ORM\Column(type="json")
     */
    private array $burdenReason = [];

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $burdenReasonOther = null;

    /**
     * @ORM\Column(type="json")
     */
    private array $typeOfDevices = [];

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $typeOfDevicesOther = null;

    /**
     * @ORM\Column(type="string", length=32)
     * @Assert\NotBlank(groups={"wizard.satisfaction-survey.diary-completion"}, message="wizard.satisfaction-survey.how-often.not-blank")
     */
    private ?string $howOftenEntriesAdded = null;

    /**
     * @ORM\Column(type="boolean")
     * @Assert\NotNull(groups={"wizard.satisfaction-survey.diary-completion"}, message="wizard.satisfaction-survey.written-note.not-blank")
     */
    private ?bool $writtenNoteKept = null;

    /**
     * @ORM\Column(type="string", length=32)
     * @Assert\NotBlank(groups={"wizard.satisfaction-survey.preferred-method"}, message="wizard.satisfaction-survey.preferred-method.not-blank")
     */
    private ?string $preferredMethod = null;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $preferredMethodOther = null;

    /**
     * @ORM\OneToOne(targetEntity=DiaryKeeper::class, inversedBy="satisfactionSurvey", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="diarykeeper_id", referencedColumnName="id", onDelete="SET NULL")
     */
    private ?DiaryKeeper $diaryKeeper = null;

    public function getEaseRating(): ?string
    {
        return $this->easeRating;
    }

    public function setEaseRating(?string $easeRating): self
    {
        $this->easeRating = $easeRating;
        return $this;
    }

    public function getBurdenRating(): ?string
    {
        return $this->burdenRating;
    }

    public function setBurdenRating(?string $burdenRating): self
    {
        $this->burdenRating = $burdenRating;
        return $this;
    }

    public function getBurdenReason(): ?array
    {
        return $this->burdenReason;
    }

    public function setBurdenReason(array $burdenReason): self
    {
        $this->burdenReason = $burdenReason;
        return $this;
    }

    public function getBurdenReasonOther(): ?string
    {
        return $this->burdenReasonOther;
    }

    public function setBurdenReasonOther(?string $burdenReasonOther): self
    {
        $this->burdenReasonOther = $burdenReasonOther;
        return $this;
    }

    public function getTypeOfDevices(): ?array
    {
        return $this->typeOfDevices;
    }

    public function setTypeOfDevices(array $typeOfDevices): self
    {
        $this->typeOfDevices = $typeOfDevices;
        return $this;
    }

    public function getTypeOfDevicesOther(): ?string
    {
        return $this->typeOfDevicesOther;
    }

    public function setTypeOfDevicesOther(?string $typeOfDevicesOther): self
    {
        $this->typeOfDevicesOther = $typeOfDevicesOther;
        return $this;
    }

    public function getHowOftenEntriesAdded(): ?string
    {
        return $this->howOftenEntriesAdded;
    }

    public function setHowOftenEntriesAdded(?string $howOftenEntriesAdded): self
    {
        $this->howOftenEntriesAdded = $howOftenEntriesAdded;
        return $this;
    }

    public function getWrittenNoteKept(): ?bool
    {
        return $this->writtenNoteKept;
    }

    public function setWrittenNoteKept(?bool $writtenNoteKept): self
    {
        $this->writtenNoteKept = $writtenNoteKept;
        return $this;
    }

    public function getPreferredMethod(): ?string
    {
        return $this->preferredMethod;
    }

    public function setPreferredMethod(?string $preferredMethod): self
    {
        $this->preferredMethod = $preferredMethod;
        return $this;
    }

    public function getPreferredMethodOther(): ?string
    {
        return $this->preferredMethodOther;
    }

    public function setPreferredMethodOther(?string $preferredMethodOther): self
    {
        $this->preferredMethodOther = $preferredMethodOther;
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

    // -----

    /**
     * @Assert\Callback(groups="wizard.satisfaction-survey.burden")
     */
    public function validateBurdenReason(ExecutionContextInterface $context): void
    {
        $burdenRating = $this->getBurdenRating();

        if ($burdenRating && $burdenRating !== '1-not-at-all-burdensome') {
            $burdenReason = $this->getBurdenReason();

            if (empty($burdenReason)) {
                $context->buildViolation("wizard.satisfaction-survey.burden-reason.not-empty")
                    ->atPath("burdenReason")
                    ->addViolation();
            }

            if (in_array('other', $burdenReason) && !$this->getBurdenReasonOther()) {
                $context->buildViolation("wizard.satisfaction-survey.burden-reason-other.not-blank")
                    ->atPath("burdenReasonOther")
                    ->addViolation();
            }
        }
    }

    /**
     * @Assert\Callback(groups="wizard.satisfaction-survey.type-of-devices")
     */
    public function validateTypeOfDevices(ExecutionContextInterface $context): void
    {
        $typeOfDevices = $this->getTypeOfDevices();

        if (empty($typeOfDevices)) {
            $context->buildViolation("wizard.satisfaction-survey.type-of-devices.not-empty")
                ->atPath("typeOfDevices")
                ->addViolation();
        }

        if (in_array('other', $typeOfDevices) && !$this->getTypeOfDevicesOther()) {
            $context->buildViolation("wizard.satisfaction-survey.type-of-devices-other.not-blank")
                ->atPath("typeOfDevicesOther")
                ->addViolation();
        }
    }

    /**
     * @Assert\Callback(groups="wizard.satisfaction-survey.preferred-method")
     */
    public function validatePreferredMethodOther(ExecutionContextInterface $context): void
    {
        $preferredMethod = $this->getPreferredMethod();

        if ($preferredMethod === 'other' && !$this->getPreferredMethodOther()) {
            $context->buildViolation("wizard.satisfaction-survey.preferred-method-other.not-blank")
                ->atPath("preferredMethodOther")
                ->addViolation();
        }
    }

    // -----
}
