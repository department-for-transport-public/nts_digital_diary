<?php

namespace App\Utility\Metrics\Events\Entity;

class JourneySplitEvent extends JourneyPersistEvent
{
    public function getName(): string
    {
        return 'Journey: split';
    }
}