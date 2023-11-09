<?php

namespace App\Entity\Feedback;

use App\Entity\IdTrait;
use App\Repository\Feedback\NoteRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=NoteRepository::class)
 * @ORM\Table(name="feedback_note")
 */
class Note
{
    use IdTrait;

    /**
     * @ORM\Column(type="text")
     * @Assert\NotBlank(groups="feedback.note", message="admin.feedback.note.not-blank")
     */
    private string $note;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $userIdentifier;

    /**
     * @ORM\Column(type="datetime")
     */
    private DateTime $createdAt;

    /**
     * @ORM\ManyToOne(targetEntity=Message::class, inversedBy="notes")
     * @ORM\JoinColumn(nullable=false)
     */
    private ?Message $message;

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(string $note): self
    {
        $this->note = $note;

        return $this;
    }

    public function getUserIdentifier(): ?string
    {
        return $this->userIdentifier;
    }

    public function setUserIdentifier(string $userIdentifier): self
    {
        $this->userIdentifier = $userIdentifier;

        return $this;
    }

    public function getCreatedAt(): ?DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getMessage(): Message
    {
        return $this->message;
    }

    public function setMessage(?Message $message): self
    {
        $this->message = $message;

        return $this;
    }
}
