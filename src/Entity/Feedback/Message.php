<?php

namespace App\Entity\Feedback;

use App\Entity\IdTrait;
use App\Repository\Feedback\MessageRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Workflow\Exception\LogicException;

#[ORM\Entity(repositoryClass: MessageRepository::class)]
#[ORM\Table(name: 'feedback_message')]
class Message
{
    public const string STATE_NEW = 'new';
    public const string STATE_ASSIGNED = 'assigned';
    public const string STATE_IN_PROGRESS = 'in-progress';
    public const string STATE_REJECTED = 'rejected';
    public const string STATE_CLOSED = 'closed';

    public const string TRANSITION_ASSIGN = 'assign';
    public const string TRANSITION_REJECT = 'reject';
    public const string TRANSITION_ACKNOWLEDGE = 'acknowledge';
    public const string TRANSITION_CLOSE = 'close';
    public const string TRANSITION_REOPEN = 'reopen';

    public const string TRANSITION_CONTEXT_ASSIGN_TO = 'assignTo';

    use IdTrait;

    #[ORM\Column(type: 'text')]
    private string $message;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $emailAddress = null;

    #[ORM\Column(type: 'string', length: 20, nullable: false, enumType: CategoryEnum::class)]
    private CategoryEnum $category = CategoryEnum::Feedback;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $assignedTo = null;

    #[ORM\Column(type: 'datetime')]
    private DateTime $sent;

    #[ORM\Column(type: 'string', length: 20)]
    private string $state = self::STATE_NEW;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $currentUserSerial = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $originalUserSerial = null;

    #[ORM\OneToMany(targetEntity: Note::class, mappedBy: 'message', orphanRemoval: true)]
    #[ORM\OrderBy(['createdAt' => 'ASC'])]
    private Collection $notes;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $page = null;

    public function __construct()
    {
        $this->notes = new ArrayCollection();
        $this->sent = new DateTime();
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getCategory(): ?CategoryEnum
    {
        return $this->category;
    }

    public function setCategory(CategoryEnum $category): self
    {
        $this->category = $category;
        return $this;
    }

    public function getAssignedTo(): ?string
    {
        return $this->assignedTo ?? null;
    }

    public function getSent(): ?DateTime
    {
        return $this->sent;
    }

    public function setSent(DateTime $sent): self
    {
        $this->sent = $sent;
        return $this;
    }

    public function getState(): ?string
    {
        return $this->state;
    }

    public function setState(string $state, array $context = []): self
    {
        $this->state = $state;

        if ($state === self::STATE_ASSIGNED) {
            if (!isset($context[self::TRANSITION_CONTEXT_ASSIGN_TO])) {
                throw new LogicException('"assignee" must be provided in transition context');
            }

            $assignTo = $context[self::TRANSITION_CONTEXT_ASSIGN_TO];

            if ($this->getAssignedTo() === $assignTo) {
                throw new LogicException('Cannot re-assign to same assignee');
            }

            $this->assignedTo = $assignTo;
        }

        return $this;
    }

    public function getEmailAddress(): ?string
    {
        return $this->emailAddress;
    }

    public function getEmailAddressOrAnon(): string
    {
        return $this->emailAddress ?? '[anonymous feedback]';
    }

    public function setEmailAddress(?string $emailAddress): self
    {
        $this->emailAddress = $emailAddress;
        return $this;
    }

    public function getCurrentUserSerial(): ?string
    {
        return $this->currentUserSerial ?? null;
    }

    public function setCurrentUserSerial(?string $currentUserSerial): self
    {
        $this->currentUserSerial = $currentUserSerial;
        return $this;
    }

    public function getOriginalUserSerial(): ?string
    {
        return $this->originalUserSerial;
    }

    public function setOriginalUserSerial(?string $originalUserSerial): self
    {
        $this->originalUserSerial = $originalUserSerial;
        return $this;
    }

    /**
     * @return Collection<int, Note>
     */
    public function getNotes(): Collection
    {
        return $this->notes;
    }

    public function addNote(Note $note): self
    {
        if (!$this->notes->contains($note)) {
            $this->notes[] = $note;
            $note->setMessage($this);
        }

        return $this;
    }

    public function removeNote(Note $note): self
    {
        if ($this->notes->removeElement($note)) {
            // set the owning side to null (unless already changed)
            if ($note->getMessage() === $this) {
                $note->setMessage(null);
            }
        }

        return $this;
    }

    public function getPage(): ?string
    {
        return $this->page ?? null;
    }

    public function getPageOrUnknown(): string
    {
        return $this->page ?? '[unknown]';
    }

    public function setPage(?string $page): self
    {
        $this->page = $page;
        return $this;
    }
}
