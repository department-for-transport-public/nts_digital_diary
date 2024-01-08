<?php

namespace App\Utility\Metrics\Events\Entity;

use App\Entity\Journey\Stage;

class StageEditEvent extends AbstractEntityEditEvent
{
    public function __construct(Stage $stage, array $changeSet)
    {
        parent::__construct($stage, $changeSet);
        $this->setDiarySerialFromDiaryKeeper($stage->getJourney()->getDiaryDay()->getDiaryKeeper());
    }

    public function getName(): string
    {
        return 'Stage: edit';
    }

    protected function getChangeSetPropertyWhitelist(): array
    {
        return [
            'number',
            'distanceTravelled.value', 'distanceTravelled.unit', 'travelTime', 'adultCount', 'childCount',
            'vehicle', 'vehicleOther',
            'ticketType',
            'isDriver', 'parkingCost.cost', 'parkingCost.hasCost',
            'ticketCost.cost', 'ticketCost.hasCost', 'boardingCount',
        ];
    }
}