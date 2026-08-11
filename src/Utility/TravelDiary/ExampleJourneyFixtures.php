<?php

namespace App\Utility\TravelDiary;

use App\DataFixtures\Definition\JourneyDefinition;
use App\DataFixtures\Definition\OtherStageDefinition;
use App\DataFixtures\Definition\PublicStageDefinition;
use App\DataFixtures\FixtureHelper;
use App\Entity\DiaryDay;
use App\Entity\DiaryKeeper;
use App\Entity\Embeddable\Distance;
use App\Entity\Journey\Journey;
use App\Repository\Journey\MethodRepository;
use ReflectionProperty;

class ExampleJourneyFixtures
{
    public function __construct(private readonly MethodRepository $methodRepository) {}

    /**
     * @return array<JourneyDefinition>
     */
    public static function getExampleJourneyFixtures(): array
    {
        return [
            new JourneyDefinition(0, 'Home', '7:00pm', 'Liverpool city centre', '8:05pm', 'Go out for meal with friends', [
                new OtherStageDefinition(1, 'walk', Distance::miles("1.0"), 18, 2, 0),
                new PublicStageDefinition(2, 'train', Distance::miles("8"), 27, 2, 0, '2.90', "Single", 1),
                new PublicStageDefinition(3, 'bus-or-coach', Distance::miles("1.5"), 8, 2, 0, '1.00', "Single", 1, "Local bus"),
            ]),

            new JourneyDefinition(0, 'Liverpool city centre', '10:30pm', 'Home', '10:55pm', 'Go home', [
                new PublicStageDefinition(1, 'taxi', Distance::miles("10"), 25, 2, 0),
            ])
        ];
    }

    public function getExampleDay(DiaryKeeper $diaryKeeper): DiaryDay
    {
        $day = (new DiaryDay())
            ->setNumber(-1)
            ->setDiaryKeeper($diaryKeeper)
        ;

        foreach (self::getExampleJourneyFixtures() as $index => $journeyDef)
        {
            $day->addJourney($journey = $this->createJourney($journeyDef, $diaryKeeper));
            try {
                $reflectionProperty = new ReflectionProperty($journey, 'id');
                $reflectionProperty->setValue($journey, "example-" . $index + 1);
            } catch (\ReflectionException $e) {}
        }

        return $day;
    }


    private function createJourney(JourneyDefinition $journeyDef, DiaryKeeper $diaryKeeper): Journey
    {
        $journey = FixtureHelper::createJourney($journeyDef, $diaryKeeper->getDiaryDayByNumber($journeyDef->getDayNumber()));
        foreach ($journeyDef->getStageDefinitions() as $stageDef) {
            $stage = FixtureHelper::createStage($stageDef, $this->methodRepository, $diaryKeeper->getHousehold()->getVehicles()->toArray());
            $journey->addStage($stage);
        }
        return $journey;
    }

}