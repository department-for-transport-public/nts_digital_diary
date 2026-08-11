<?php

namespace App\Utility\TravelDiary;

use App\Entity\Journey\Journey;
use App\Entity\Journey\Stage;
use Ghost\GovUkCoreBundle\FormWizard\PropertyMerger;

class DuplicateJourneyHelper
{
    public const string JOURNEY_TYPE_REPEAT = 'repeat';
    public const string JOURNEY_TYPE_RETURN = 'return';


    public function __construct(protected readonly PropertyMerger $propertyMerger)
    {}

    public function cloneSourceJourney(Journey $sourceJourney, string $journeyType): Journey
    {
        $journey = $this->propertyMerger->clone($sourceJourney, $this->getCloneProperties($journeyType, Journey::class));

        if ($journeyType === self::JOURNEY_TYPE_RETURN) {
            // Swap start/end locations
            $journey->setStartLocation($sourceJourney->getEndLocation());
            $journey->setIsStartHome($sourceJourney->getIsEndHome());
            $journey->setEndLocation($sourceJourney->getStartLocation());
            $journey->setIsEndHome($sourceJourney->getIsStartHome());
        }

        $stages = match($journeyType) {
            self::JOURNEY_TYPE_REPEAT => $sourceJourney->getStages(),
            self::JOURNEY_TYPE_RETURN => array_reverse($sourceJourney->getStages()->toArray())
        };
        $number = 1;
        foreach ($stages as $stage)
        {
            /** @var Stage $stage */
            $stage = $this->propertyMerger->clone($stage, $this->getCloneProperties($journeyType, Stage::class));
            if ($journeyType === self::JOURNEY_TYPE_RETURN) $stage->setNumber($number++);
            $journey->addStage($stage);
        }
        return $journey;
    }

    protected function getCloneProperties(string $journeyType, string $class): array
    {
        return match ($journeyType) {
            self::JOURNEY_TYPE_REPEAT => $class::REPEAT_JOURNEY_CLONE_PROPERTIES,
            self::JOURNEY_TYPE_RETURN => $class::RETURN_JOURNEY_CLONE_PROPERTIES,
        };
    }
}