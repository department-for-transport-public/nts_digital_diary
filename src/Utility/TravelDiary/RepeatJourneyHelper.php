<?php

namespace App\Utility\TravelDiary;

use App\Entity\Journey\Journey;
use App\Entity\Journey\Stage;
use App\FormWizard\PropertyMerger;

class RepeatJourneyHelper
{
    private PropertyMerger $propertyMerger;

    private const JOURNEY_CLONE_PROPERTIES = [
        /* purpose form */      'purpose',
        /* locations form */    'startLocation', 'isStartHome', 'endLocation', 'isEndHome',
    ];

    public function __construct(PropertyMerger $propertyMerger)
    {
        $this->propertyMerger = $propertyMerger;
    }

    public function cloneSourceJourney(Journey $sourceJourney): Journey
    {
        $journey = $this->propertyMerger->clone($sourceJourney, self::JOURNEY_CLONE_PROPERTIES);
        foreach ($sourceJourney->getStages() as $stage)
        {
            /** @var Stage $stage */
            $stage = $this->propertyMerger->clone($stage, Stage::REPEAT_JOURNEY_CLONE_PROPERTIES);
            $journey->addStage($stage);
        }
        return $journey;
    }
}