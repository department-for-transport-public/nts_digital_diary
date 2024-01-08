<?php

namespace App\Utility\Metrics\Events\Entity;

use App\Entity\Journey\Journey;

class JourneyDeleteEvent extends AbstractEntityDeleteEvent
{
    public function __construct(Journey $journey, string $originalId)
    {
        parent::__construct($originalId);
        $this->setDiarySerialFromDiaryKeeper($journey->getDiaryDay()->getDiaryKeeper());
    }

    public function getName(): string
    {
        return 'Journey: delete';
    }
}