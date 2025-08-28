<?php

namespace App\Utility\TravelDiary;

use App\DataFixtures\Definition\JourneyDefinition;
use App\DataFixtures\Definition\OtherStageDefinition;
use App\DataFixtures\Definition\PublicStageDefinition;
use App\Entity\Embeddable\Distance;

class PracticeDayExampleJourneyFixtures
{
    /**
     * @return array<JourneyDefinition>
     */
    public static function getPracticeDayExampleJourneyFixtures(): array
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

}