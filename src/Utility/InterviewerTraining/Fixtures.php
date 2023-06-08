<?php

namespace App\Utility\InterviewerTraining;

use App\DataFixtures\Definition\JourneyDefinition;
use App\DataFixtures\Definition\OtherStageDefinition;
use App\DataFixtures\Definition\PrivateStageDefinition;
use App\DataFixtures\Definition\PublicStageDefinition;
use App\Entity\Distance;

class Fixtures
{
    /**
     * @return array<JourneyDefinition>
     */
    public static function getJourneyFixturesForCorrection(): array
    {
        return [
            // day 1
            new JourneyDefinition(1, 'Home', '10:00am', 'Home', '11:30am', 'To walk the dog', [
                new OtherStageDefinition(1, 'walk', Distance::miles("3"), 90, 1, 0),
            ]),
            new JourneyDefinition(1, 'Home', '1:05pm', 'Placebury City Centre', '1:35pm', 'To go clothes shopping', [
                new OtherStageDefinition(1, 'walk', Distance::miles("0.2"), 5, 1, 0),
                new PublicStageDefinition(2, 'bus-or-coach', Distance::miles("8"), 25, 1, 0, 530, "Adult return", 1, "Local bus"),
            ]),
            new JourneyDefinition(1, 'Placebury City Centre', '3:30pm', 'Home', '4:05pm', 'Go home', [
                new PublicStageDefinition(1, 'bus-or-coach', Distance::miles("8"), 25, 1, 0, 0, "Adult return", 1, "Local bus"),
                new OtherStageDefinition(2, 'walk', Distance::miles("0.2"), 5, 1, 0),
            ]),

            // day 2
            new JourneyDefinition(2, 'Home', '8:00am', 'The Work Space, Placebury', '8:35am', 'To go to work', [
                new PublicStageDefinition(1, 'train', Distance::miles("8"), 30, 1, 0, 730, "Adult return", 1),
            ]),
            new JourneyDefinition(2, 'Home', '7:00pm', 'The Red Lion, Townton', '7:30pm', 'To meet friends for a drink', [
                new OtherStageDefinition(1, 'walk', Distance::miles("1.5"), 30, 2, 0),
            ]),
            new JourneyDefinition(2, 'The Red Lion, Townton', '11:10pm', 'Home', '11:45am', 'Go home', [
                new OtherStageDefinition(1, 'walk', Distance::miles("1.5"), 35, 2, 0),
            ]),

            // day 3
            new JourneyDefinition(3, 'Home', '5:00pm', 'Placebury City Centre', '5:30pm', 'To go to shops', [
                new PrivateStageDefinition(1, 'car', Distance::miles("8"), 30, 2, 1, true,350, 0)
            ]),
            new JourneyDefinition(3, 'Placebury City Centre', '7:00pm', 'Home', '7:30pm', 'Go home', [
                new PrivateStageDefinition(1, 'car', Distance::miles("8"), 30, 2, 1, true, 0, 0)
            ]),

            // day 4
            new JourneyDefinition(4, 'Home', '8:10am', 'The Work Space, Placebury', '9:00am', 'To go to work', [
                new PublicStageDefinition(1, 'train', Distance::miles("8"), 20, 1, 0, 730, "Adult return", 1),
                new OtherStageDefinition(2, 'walk', Distance::miles("1.5"), 30, 1, 0),
            ]),
            new JourneyDefinition(4, 'The Work Space, Placebury', '5:15pm', 'Home', '6:15pm', 'Go home', [
                new PublicStageDefinition(1, 'train', Distance::miles("8"), 20, 1, 0, 0, "Adult return", 1),
            ]),
            new JourneyDefinition(4, 'Home', '8:00pm', 'Placebury City Centre', '8:30pm', 'Socialising', [
                new PrivateStageDefinition(1, 'car', Distance::miles("10"), 30, 1, 0, true,0, "Friend's car")
            ]),

            // day 5
            new JourneyDefinition(5, 'Home', '9:45am', 'Bonnymead Park, Townton', '10:15am', 'To walk the dog', [
                new OtherStageDefinition(1, 'walk', Distance::miles("1.5"), 30, 1, 0),
            ]),
            new JourneyDefinition(5, 'Bonnymean Park, Townton', '11:00am', 'Home', '11:30am', 'Go home', [
                new OtherStageDefinition(1, 'walk', Distance::miles("1.5"), 30, 1, 0),
            ]),
            new JourneyDefinition(5, 'Home', '12:00pm', 'Placebury City Centre', '1:00pm', 'To meet a friend for lunch', [
                new PrivateStageDefinition(1, 'car', Distance::miles("15"), 30, 1, 0, true,350, 0),
                new PublicStageDefinition(2, 'train', Distance::miles("10"), 15, 2, 0, 480, "Adult return", 1),
                new OtherStageDefinition(3, 'walk', Distance::miles("1"), 15, 2, 0),
            ]),
            new JourneyDefinition(5, 'Placebury City Centre', '3:30pm', 'Home', '4:45pm', 'Go home', [
                new OtherStageDefinition(1, 'walk', Distance::miles("1"), 20, 2, 0),
                new PublicStageDefinition(2, 'train', Distance::miles("10"), 20, 2, 0, 0, "Adult return", 1),
                new PrivateStageDefinition(3, 'car', Distance::miles("15"), 30, 1, 0, true,0, 0),
            ]),
        ];
    }
}