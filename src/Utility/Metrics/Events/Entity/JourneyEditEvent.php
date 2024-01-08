<?php

namespace App\Utility\Metrics\Events\Entity;

use App\Entity\Journey\Journey;

class JourneyEditEvent extends AbstractEntityEditEvent
{
    public function __construct(Journey $journey, array $changeSet)
    {
        parent::__construct($journey, $changeSet);
        $this->setDiarySerialFromDiaryKeeper($journey->getDiaryDay()->getDiaryKeeper());
    }

    public function getName(): string
    {
        return 'Journey: edit';
    }

    protected function getChangeSetPropertyWhitelist(): array
    {
        return [
            'purpose',
            'startTime', 'endTime',
            'startLocation', 'isStartHome', 'endLocation', 'isEndHome',
            'sharedTo'
        ];
    }
}