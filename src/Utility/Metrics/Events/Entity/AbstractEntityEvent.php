<?php

namespace App\Utility\Metrics\Events\Entity;

use App\Utility\Metrics\Events\AbstractEvent;

abstract class AbstractEntityEvent extends AbstractEvent
{
    public function __construct(object $object)
    {
        $this->metadata['id'] = $object->getId();
    }
}