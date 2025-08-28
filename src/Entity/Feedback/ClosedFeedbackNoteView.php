<?php

namespace App\Entity\Feedback;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity]
#[ORM\Table(name: "vw_closed_feedback_note_stats")]
class ClosedFeedbackNoteView
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    protected int $id;

    #[ORM\OneToOne(targetEntity: Message::class)]
    protected Message $message;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    protected \DateTime $createdAt;

    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return Message
     */
    public function getMessage(): Message
    {
        return $this->message;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }
}