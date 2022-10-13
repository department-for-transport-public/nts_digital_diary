<?php

namespace App\Messenger\PropertyChange;

class EntityChangeMessage extends AbstractMessage
{
    protected string $entityId;
    protected string $entityClass;
    protected array $fieldsChanged;

    public function __construct(string $entityId, string $entityClass, array $fieldsChanged)
    {
        $this->entityId = $entityId;
        $this->entityClass = $entityClass;
        $this->fieldsChanged = $fieldsChanged;
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