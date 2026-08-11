<?php

namespace App\Utility\Metrics\Events\Entity;

use App\Entity\Journey\Journey;
use App\Entity\Journey\Stage;
use App\Utility\Metrics\Events\AbstractEvent;
use App\Utility\Metrics\Events\FormWizardEventInterface;
use Symfony\Component\Form\Form;

abstract class JourneyPersistWithStagesEvent extends JourneyPersistEvent
{
    public function __construct(Journey $journey)
    {
        parent::__construct($journey);

        $this->metadata['stages'] = array_reduce(
            $journey->getStages()->toArray(),
            function(array $carry, Stage $s) {
                $carry[$s->getNumber()] = $s->getId();
                return $carry;
            },
            []
        );
    }
}