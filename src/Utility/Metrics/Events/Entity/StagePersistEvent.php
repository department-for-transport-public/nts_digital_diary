<?php

namespace App\Utility\Metrics\Events\Entity;

use App\Entity\Journey\Stage;
use App\Utility\Metrics\Events\AbstractEvent;
use App\Utility\Metrics\Events\FormWizardEventInterface;

class StagePersistEvent extends AbstractEntityEvent implements FormWizardEventInterface
{
    public function __construct(Stage $stage)
    {
        $this->setDiarySerialFromDiaryKeeper($stage->getJourney()->getDiaryDay()->getDiaryKeeper());
        $this->metadata['number'] = $stage->getNumber();
        $this->metadata['journeyId'] = $stage->getJourney()->getId();
        parent::__construct($stage);
    }

    public function getName(): string
    {
        return 'Stage: create';
    }
}