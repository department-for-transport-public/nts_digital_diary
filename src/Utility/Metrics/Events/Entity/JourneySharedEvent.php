<?php

namespace App\Utility\Metrics\Events\Entity;

use App\Entity\Journey\Journey;

class JourneySharedEvent extends JourneyPersistEvent
{
    public function __construct(Journey $journey, array $sharedToIds)
    {
        $this->metadata['shared_to'] = $sharedToIds;
        parent::__construct($journey);
        // JourneyPersistEvent adds the day, but we don't want it here
        unset($this->metadata['day']);
    }

    public function getName(): string
    {
        return 'Journey: share';
    }
}