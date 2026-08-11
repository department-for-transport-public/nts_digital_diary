<?php

namespace App\Utility\Metrics\Events\Entity;

class JourneyRepeatEvent extends JourneyPersistWithStagesEvent
{
    public function getName(): string
    {
        return 'Journey: repeat';
    }
}