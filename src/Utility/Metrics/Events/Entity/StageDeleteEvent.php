<?php

namespace App\Utility\Metrics\Events\Entity;

use App\Entity\Journey\Stage;

class StageDeleteEvent extends AbstractEntityDeleteEvent
{
    public function __construct(Stage $stage, string $originalId)
    {
        parent::__construct($originalId);
        $this->setDiarySerialFromDiaryKeeper($stage->getJourney()->getDiaryDay()->getDiaryKeeper());
    }

    public function getName(): string
    {
        return 'Stage: delete';
    }
}