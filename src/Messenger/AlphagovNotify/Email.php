<?php

namespace App\Messenger\AlphagovNotify;

class Email extends AbstractMessage
{
    protected string $emailAddress;

    protected ?string $emailReplyToId;

    public function __construct(string $eventName, string $originatingEntityClass, string $originatingEntityId, string $emailAddress, string $templateId, $personalisation = [], ?string $reference = null, ?string $senderId = null)
    {
        parent::__construct($eventName, $originatingEntityClass, $originatingEntityId, $templateId, $personalisation, $reference);
        $this->emailAddress = $emailAddress;
        $this->emailReplyToId = $senderId;
    }

    public function getSendMethodParameters(): array
    {
        return [
            $this->emailAddress,
            $this->getTemplateId(),
            $this->getPersonalisation(),
            $this->getReference(),
            $this->emailReplyToId,
        ];
    }

    public function getPersonalisation(): ?array
    {
        return array_merge(parent::getPersonalisation(), ['email address' => $this->getEmailAddress()]);
    }

    public function getEmailAddress(): ?string
    {
        return $this->emailAddress;
    }

    public function setEmailAddress(?string $emailAddress): self
    {
        $this->emailAddress = $emailAddress;
        return $this;
    }

    public function getEmailReplyToId(): ?string
    {
        return $this->emailReplyToId;
    }

    public function setEmailReplyToId(?string $emailReplyToId): self
    {
        $this->emailReplyToId = $emailReplyToId;
        return $this;
    }
}