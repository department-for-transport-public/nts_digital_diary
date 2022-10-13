<?php

namespace App\Messenger\PropertyChange;

use App\Entity\PropertyChangeLog;

class EntityDeletionMessage extends AbstractMessage
{
    protected string $entityId;
    protected string $entityClass;

    public function __construct(string $entityId, string $entityClass)
    {
        $this->entityId = $entityId;
        $this->entityClass = $entityClass;
    }

    public static function fromChangeLog(PropertyChangeLog $changeLog): EntityDeletionMessage
    {
        return new EntityDeletionMessage($changeLog->getEntityId(), $changeLog->getEntityClass());
    }

    public function getEntityId(): string
    {
        return $this->entityId;
    }

    public function getEntityClass(): string
    {
        return $this->entityClass;
    }
}