<?php

namespace App\Messenger\AlphagovNotify;

class Sms extends AbstractMessage
{
    public function __construct(
        string            $eventName,
        string            $originatingEntityClass,
        string            $originatingEntityId,
        protected string  $phoneNumber,
        string            $templateId,
        ?array            $personalisation = [],
        ?string           $reference = null,
        protected ?string $senderId = null
    ) {
        parent::__construct($eventName, $originatingEntityClass, $originatingEntityId, $templateId, $personalisation, $reference);
    }

    public function getSendMethodParameters(): array
    {
        return [
            $this->phoneNumber,
            $this->getTemplateId(),
            $this->getPersonalisation(),
            $this->getReference(),
            $this->senderId,
        ];
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(?string $phoneNumber): self
    {
        $this->phoneNumber = $phoneNumber;
        return $this;
    }

    public function getSenderId(): ?string
    {
        return $this->senderId;
    }

    public function setSenderId(?string $senderId): self
    {
        $this->senderId = $senderId;
        return $this;
    }

    public function getRecipient(): ?string
    {
        return $this->phoneNumber;
    }
}