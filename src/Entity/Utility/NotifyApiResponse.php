<?php

namespace App\Entity\Utility;

use App\Entity\IdTrait;
use App\Repository\Utility\NotifyApiResponseRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NotifyApiResponseRepository::class)]
class NotifyApiResponse
{
    use IdTrait;

    #[ORM\Column(type: Types::STRING, length: 32)]
    private ?string $eventName = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $messageClass = null;

    #[ORM\Column(type: Types::JSON)]
    private array $data = [];

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTime $timestamp = null;

    /**
     * A hash of the recipient (i.e. address, sms or email -> unique string)
     */
    #[ORM\Column(type: Types::STRING, length: 64, nullable: true)]
    private ?string $recipientHash = null;

    /**
     * The recipient as a string
     *
     * NOTE: We weren't sure which format to use, so are including both for the moment
     *
     *       These fields won't be necessary if we transition to Survey -> Invitation -> NotifyApiResponses,
     *       but that will take a while.
     */
    #[ORM\Column(type: Types::STRING, length: 1024, nullable: true)]
    private ?string $recipient = null;

    public function getEventName(): ?string
    {
        return $this->eventName;
    }

    public function setEventName(string $eventName): self
    {
        $this->eventName = $eventName;
        return $this;
    }

    public function getMessageClass(): ?string
    {
        return $this->messageClass;
    }

    public function setMessageClass(string $messageClass): self
    {
        $this->messageClass = $messageClass;
        return $this;
    }

    public function getData(): ?array
    {
        return $this->data;
    }

    public function setData(array $data): self
    {
        $this->data = $data;
        return $this;
    }

    public function getTimestamp(): ?\DateTime
    {
        return $this->timestamp;
    }

    public function setTimestamp(\DateTime $timestamp): self
    {
        $this->timestamp = $timestamp;
        return $this;
    }

    public function getRecipientHash(): ?string
    {
        return $this->recipientHash;
    }

    public function setRecipientHash(?string $recipientHash): self
    {
        $this->recipientHash = $recipientHash;
        return $this;
    }

    public function getRecipient(): ?string
    {
        return $this->recipient;
    }

    public function setRecipient(?string $recipient): self
    {
        $this->recipient = $recipient;
        return $this;
    }
}
