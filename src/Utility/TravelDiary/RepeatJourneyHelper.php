<?php

namespace App\Utility\TravelDiary;

use App\Entity\Journey\Journey;
use App\Entity\Journey\Stage;
use Ghost\GovUkCoreBundle\FormWizard\PropertyMerger;

class RepeatJourneyHelper
{
    public function __construct(protected readonly PropertyMerger $propertyMerger)
    {}

    public function cloneSourceJourney(Journey $sourceJourney): Journey
    {
        $journey = $this->propertyMerger->clone($sourceJourney, Journey::REPEAT_JOURNEY_CLONE_PROPERTIES);
        foreach ($sourceJourney->getStages() as $stage)
        {
            /** @var Stage $stage */
            $stage = $this->propertyMerger->clone($stage, Stage::REPEAT_JOURNEY_CLONE_PROPERTIES);
            $journey->addStage($stage);
        }
        return $journey;
    }
}