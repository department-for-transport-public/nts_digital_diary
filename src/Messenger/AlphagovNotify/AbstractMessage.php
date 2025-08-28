<?php

namespace App\Messenger\AlphagovNotify;

use App\Messenger\AbstractAsyncMessage;
use App\Utility\AlphagovNotify\Reference;
use Doctrine\Common\Util\ClassUtils;
use ReflectionClass;

abstract class AbstractMessage extends AbstractAsyncMessage
{
    protected ?string $originatingEntityClass;
    protected ?string $reference;

    public function __construct(
        protected ?string $eventName,
        ?string           $originatingEntityClass,
        protected ?string $originatingEntityId,
        protected ?string $templateId,
        protected ?array  $personalisation = [],
        ?string           $reference = null
    )
    {
        $this->originatingEntityClass = ($originatingEntityClass !== null) ?
            ClassUtils::getRealClass($originatingEntityClass) :
            null;
        if (empty($reference)) {
            if ($this->originatingEntityClass && $this->originatingEntityId) {
                $this->reference = "$this->originatingEntityClass::$this->originatingEntityId";
            } else {
                $this->reference = '';
            }
        } else {
            $this->reference = $reference;
        }
    }

    abstract public function getSendMethodParameters(): array;

    public function getTemplateId(): ?string
    {
        return $this->templateId;
    }

    public function setTemplateId(?string $templateId): self
    {
        $this->templateId = $templateId;
        return $this;
    }

    public function getPersonalisation(): ?array
    {
        return $this->personalisation;
    }

    public function setPersonalisation(?array $personalisation): self
    {
        $this->personalisation = $personalisation;
        return $this;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): self
    {
        $this->reference = $reference;
        return $this;
    }

    public function getOriginatingEntityClass(): ?string
    {
        return $this->originatingEntityClass;
    }

    public function getOriginatingEntityId(): ?string
    {
        return $this->originatingEntityId;
    }

    public function getEventName(): ?string
    {
        return $this->eventName;
    }

    /**
     * @return false|string
     */
    public function getTemplateReferenceConstantName()
    {
        $templateReferenceReflection = new ReflectionClass(Reference::class);
        return array_search($this->getTemplateId(), $templateReferenceReflection->getConstants());

    }
}