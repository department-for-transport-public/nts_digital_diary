<?php

namespace App\Utility\Metrics\Events\Entity;

use App\Entity\Journey\Journey;
use App\Utility\Metrics\Events\AbstractEvent;
use App\Utility\Metrics\Events\FormWizardEventInterface;
use Symfony\Component\Form\Form;

class JourneyPersistEvent extends AbstractEntityEvent implements FormWizardEventInterface
{
    public function __construct(Journey $journey)
    {
        $this->setDiarySerialFromDiaryKeeper($journey->getDiaryDay()->getDiaryKeeper());
        $this->metadata['day'] = $journey->getDiaryDay()->getNumber();
        parent::__construct($journey);
    }

    public function getName(): string
    {
        return 'Journey: create';
    }
}