<?php

namespace App\Utility\Metrics\Events\Entity;

class JourneyReturnEvent extends JourneyPersistWithStagesEvent
{
    public function getName(): string
    {
        return 'Journey: return';
    }
}