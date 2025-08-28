<?php

namespace App\Messenger\PropertyChange;

class EntityChangeMessage extends AbstractMessage
{
    public function __construct(protected string $entityId, protected string $entityClass, protected array $fieldsChanged)
    {
    }

    public function getEntityId(): string
    {
        return $this->entityId;
    }

    public function getEntityClass(): string
    {
        return $this->entityClass;
    }

    public function getFieldsChanged(): array
    {
        return $this->fieldsChanged;
    }
}