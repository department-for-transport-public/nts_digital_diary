<?php

namespace App\Messenger\AlphagovNotify;

use App\Utility\AlphagovNotify\Reference;
use Doctrine\Common\Util\ClassUtils;

abstract class AbstractSendMessage extends AbstractMessage
{
    protected ?string $originatingEntityClass;
    protected ?string $reference;

    public function __construct(protected ?string $eventName, string $originatingEntityClass, protected ?string $originatingEntityId, protected ?string $templateId, protected ?string $recipient, protected ?array $personalisation = [], ?string $reference = null)
    {
        $this->originatingEntityClass = ClassUtils::getRealClass($originatingEntityClass);
        $this->reference = $reference ?? $this->buildReference();
    }

    public function buildReference(): string
    {
        return "{$this->originatingEntityClass}::{$this->originatingEntityId}::{$this->getRecipientHash()}";
    }

    abstract public function getEndpoint(): string;
    abstract public function getSendMethodParameters(): array;

    public function getTemplateId(): string
    {
        return $this->templateId;
    }

    public function getPersonalisation(): ?array
    {
        return $this->personalisation;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function getOriginatingEntityId(): ?string
    {
        return $this->originatingEntityId;
    }

    public function getOriginatingEntityClass(): ?string
    {
        return $this->originatingEntityClass;
    }

    public function getEventName(): ?string
    {
        return $this->eventName;
    }

    public function getTemplateReferenceConstantName(): ?string
    {
        $templateReferenceReflection = new \ReflectionClass(Reference::class);
        $constantName = array_search($this->getTemplateId(), $templateReferenceReflection->getConstants());

        return $constantName ?: null;
    }

    public function getRecipient(): ?string
    {
        return $this->recipient;
    }

    public function getRecipientHash(): ?string
    {
        return $this->recipient ? hash('sha256', $this->recipient) : null;
    }
}