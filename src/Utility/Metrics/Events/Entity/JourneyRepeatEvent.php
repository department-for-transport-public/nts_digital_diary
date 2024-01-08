<?php

namespace App\Utility\Metrics\Events\Entity;

class JourneyRepeatEvent extends JourneyPersistEvent
{
    public function getName(): string
    {
        return 'Journey: repeat';
    }
}