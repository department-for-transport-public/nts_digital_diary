<?php

namespace App\Utility\Metrics\Events\Entity;

use App\Entity\Journey\Journey;
use App\Utility\Metrics\Events\FormWizardEventInterface;

class JourneyPersistEvent extends AbstractEntityEvent implements FormWizardEventInterface
{
    public function __construct(Journey $journey)
    {
        parent::__construct($journey);
        $this->setDiarySerialFromDiaryKeeper($journey->getDiaryDay()->getDiaryKeeper());
        $this->metadata['day'] = $journey->getDiaryDay()->getNumber();
    }

    public function getName(): string
    {
        return 'Journey: create';
    }
}