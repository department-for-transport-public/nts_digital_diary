<?php

namespace App\Utility\Metrics\Events\Entity;

use App\Utility\Metrics\Events\AbstractEvent;

abstract class AbstractEntityDeleteEvent extends AbstractEvent
{
    public function __construct(string $originalId)
    {
        $this->metadata = [
            'id' => $originalId,
        ];
    }
}