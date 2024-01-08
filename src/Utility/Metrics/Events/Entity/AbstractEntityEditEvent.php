<?php

namespace App\Utility\Metrics\Events\Entity;

use App\Utility\Metrics\Events\AbstractEvent;

abstract class AbstractEntityEditEvent extends AbstractEntityEvent
{
    abstract protected function getChangeSetPropertyWhitelist(): array;

    public function __construct(object $object, array $changeSet)
    {
        $this->handleChangeSet($changeSet);
        parent::__construct($object);
    }

    protected function handleChangeSet(array $changeSet): void
    {
        $changedProperties = array_keys($changeSet);
        $this->metadata['changed'] = array_intersect($changedProperties, $this->getChangeSetPropertyWhitelist());
    }
}