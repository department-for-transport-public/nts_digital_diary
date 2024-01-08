<?php

namespace App\Utility\Metrics\Events\Entity;

class JourneyReturnEvent extends JourneyPersistEvent
{
    public function getName(): string
    {
        return 'Journey: return';
    }
}