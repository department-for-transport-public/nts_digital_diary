<?php

namespace App\Utility\InterviewerTraining;

use App\DataFixtures\Definition\JourneyDefinition;
use App\DataFixtures\Definition\OtherStageDefinition;
use App\DataFixtures\Definition\PrivateStageDefinition;
use App\DataFixtures\Definition\PublicStageDefinition;
use App\Entity\Embeddable\Distance;

class Fixtures
{
    /**
     * @return array<JourneyDefinition>
     */
    public static function getFirstDiaryKeeperJourneyFixturesForCorrection(): array
    {
        return [
            // day 1
            new JourneyDefinition(1, 'Home', '10:00am', 'Bonnymead Park, Townton', '10:30am', 'To walk the dog', [
                new OtherStageDefinition(1, 'walk', Distance::miles("1.0"), 30, 1, 0),
            ]),
            new JourneyDefinition(1, 'Bonnymead Park, Townton', '11:00am', 'Home', '11:30am', 'Go home', [
                new OtherStageDefinition(1, 'walk', Distance::miles("1.0"), 30, 1, 0),
            ]),

            new JourneyDefinition(1, 'Home', '1:05pm', 'B&Q Salisbury, Wiltshire', '1:35pm', 'To go shopping', [
                new OtherStageDefinition(1, 'walk', Distance::miles("0.2"), 5, 1, 0),
                new PublicStageDefinition(2, 'bus-or-coach', Distance::miles("8"), 25, 1, 0, "5.30", "Adult return", 1, "Local Activ8 bus"),
            ]),
            new JourneyDefinition(1, 'B&Q Salisbury, Wiltshire', '3:30pm', 'Home', '4:05am', 'Go home', [
                new PublicStageDefinition(1, 'bus-or-coach', Distance::miles("8"), 25, 1, 0, '0', "Adult return", 1, "Local Activ8 bus"),
                new OtherStageDefinition(2, 'walk', Distance::miles("0.2"), 5, 1, 0),
            ]),

            // day 2
            new JourneyDefinition(2, 'Home', '7:00am', 'The Work Space, Bath', '8:10am', 'To go to work', [
                new PublicStageDefinition(1, 'train', Distance::miles("18"), 60, 1, 0, '17.30', "Adult return", 1),
            ]),
            new JourneyDefinition(2, 'Home', '7:00pm', 'The Red Lion, Townton', '7:30pm', 'To meet friends for a drink', [
                new OtherStageDefinition(1, 'walk', Distance::miles("1.0"), 30, 1, 0),
            ]),
            new JourneyDefinition(2, 'The Red Lion, Townton', '11:10pm', 'Home', '11:45am', 'Go home', [
                new OtherStageDefinition(1, 'walk', Distance::miles("1.0"), 35, 1, 0),
            ]),

            // day 3
            new JourneyDefinition(3, 'Home', '10:00am', 'Home', '11:30am', 'To walk the dog', [
                new OtherStageDefinition(1, 'walk', Distance::miles("2"), 90, 2, 0),
            ]),
            new JourneyDefinition(3, 'Home', '3:00pm', 'Salisbury City Centre', '3:30pm', 'To go clothes shopping', [
                new PrivateStageDefinition(1, 'car', Distance::miles("8"), 30, 1, 0, true,"3.50", 0)
            ]),
            new JourneyDefinition(3, 'Salisbury City Centre', '6:05pm', 'Home', '6:40pm', 'Go home', [
                new PrivateStageDefinition(1, 'car', Distance::miles("8"), 35, 1, 0, true, '0', 0)
            ]),

            // day 4
            new JourneyDefinition(4, 'Home', '8:10am', 'Bodyworks Gym, Warminster', '9:00am', 'To go to the gym', [
                new PublicStageDefinition(1, 'train', Distance::miles("20"), 25, 1, 0, "6.10", "Adult return", 1),
                new OtherStageDefinition(2, 'walk', Distance::miles("1.5"), 25, 1, 0),
            ]),
            new JourneyDefinition(4, 'Bodyworks Gym, Warminster', '12:00pm', 'Home', '1:10pm', 'Go home', [
                new PublicStageDefinition(1, 'train', Distance::miles("20"), 30, 1, 0, '0', "Adult return", 1),
            ]),
            new JourneyDefinition(4, 'Home', '8:00pm', 'Salisbury', '8:30pm', 'Socialising', [
                new PrivateStageDefinition(1, 'car', Distance::miles("9"), 30, 1, 0, true,'0', 0)
            ]),
            new JourneyDefinition(4, 'Friend\'s house, Bishopdown Farm, Salisbury', '10:30pm', 'Home', '11:00pm', 'Go home', [
                new PrivateStageDefinition(1, 'car', Distance::miles("9"), 30, 1, 0, true,'0', 0)
            ]),

            // day 5
            new JourneyDefinition(5, 'Home', '9:45am', 'Bonnymead Park, Townton', '10:15am', 'To walk the dog', [
                new OtherStageDefinition(1, 'walk', Distance::miles("1.0"), 30, 1, 0),
            ]),
            new JourneyDefinition(5, 'Bonnymean Park, Townton', '10:45am', 'Home', '11:15am', 'Go home', [
                new OtherStageDefinition(1, 'walk', Distance::miles("1.0"), 30, 1, 0),
            ]),
            new JourneyDefinition(5, 'Home', '12:00pm', 'Bath City Centre', '1:05pm', 'To have lunch with a friend', [
                new PrivateStageDefinition(1, 'car', Distance::miles("8"), 25, 1, 0, true,"3.50", 0),
                new PublicStageDefinition(2, 'train', Distance::miles("10"), 20, 1, 0, "4.80", "Adult off-peak return", 1),
                new OtherStageDefinition(3, 'walk', Distance::miles("1"), 20, 1, 0),
            ]),
            new JourneyDefinition(5, 'Bath City Centre', '3:30pm', 'Home', '4:45pm', 'Go home', [
                new OtherStageDefinition(1, 'walk', Distance::miles("1"), 25, 1, 0),
                new PublicStageDefinition(2, 'train', Distance::miles("10"), 25, 1, 0, '0', "Adult off-peak return", 1),
                new PrivateStageDefinition(3, 'car', Distance::miles("8"), 30, 1, 0, true,'0', 0),
            ]),

            // day 6


            // day 7
            new JourneyDefinition(7, 'Home', '10:30am', 'Ocean Village Retail Park, Southampton', '11:50am', 'To go shoe shopping', [
                new PrivateStageDefinition(1, 'car', Distance::miles("8"), 30, 2, 0, false, "3.50", 0),
                new PublicStageDefinition(2, 'train', Distance::miles("25"), 40, 2, 0, "12.80", "Adult off-peak return", 1)
            ]),
            new JourneyDefinition(7, 'Home', '10:30am', 'Ocean village, Southampton', '11:50am', 'Same as john', [
                new PrivateStageDefinition(1, 'car', Distance::miles("8"), 30, 2, 0, false, "3.50", 0),
                new PublicStageDefinition(2, 'train', Distance::miles("25"), 40, 2, 0, "12.80", "Adult return", 1)
            ]),
            new JourneyDefinition(7, 'Ocean Village Retail Park, Southampton', '2:35pm', 'Home', '3:50pm', 'Go home', [
                new PublicStageDefinition(1, 'train', Distance::miles("25"), 45, 2, 0, '0', "Adult off-peak return", 1),
                new PrivateStageDefinition(2, 'car', Distance::miles("8"), 25, 2, 0, false, '0', 0),
            ]),
        ];
    }

    public static function getSecondDiaryKeeperJourneyFixturesForCorrection(): array
    {
        return [
            // day 1

            // day 2
            new JourneyDefinition(2, 'Home', '10:00am', 'Bonnymead Park, Townton', '10:25am', 'To walk the dog', [
                new OtherStageDefinition(1, 'walk', Distance::miles("1"), 25, 1, 0),
            ]),
            new JourneyDefinition(2, 'Bonnymead Park, Townton', '11:00am', 'Home', '11:25am', 'Go home', [
                new OtherStageDefinition(1, 'walk', Distance::miles("1"), 25, 1, 0),
            ]),

            // day 3
            new JourneyDefinition(3, 'Home', '10:00am', 'Bonnymead Park, Townton', '10:45am', 'To walk the dog', [
                new OtherStageDefinition(1, 'walk', Distance::miles("1"), 45, 2, 0),
            ]),
            new JourneyDefinition(3, 'Bonnymead Park, Townton', '10:45am', 'Home', '11:30am', 'Go home', [
                new OtherStageDefinition(1, 'walk', Distance::miles("1"), 45, 2, 0),
            ]),

            // day 4
            new JourneyDefinition(4, 'Home', '8:30am', 'Bonnymead Park, Townton', '9:00am', 'To walk the dog', [
                new OtherStageDefinition(1, 'walk', Distance::miles("1"), 30, 1, 0),
            ]),
            new JourneyDefinition(4, 'Bonnymead Park, Townton', '9:30am', 'Home', '10:05am', 'Go home', [
                new OtherStageDefinition(1, 'walk', Distance::miles("1"), 35, 1, 0),
            ]),

            // day 5
            new JourneyDefinition(5, 'Home', '9:00am', 'Piccadilly Circus, London', '11:40am', 'To shopping for books', [
                new PrivateStageDefinition(1, 'car', Distance::miles("8"), 30, 1, 0, true,"6.00", 1),
                new PublicStageDefinition(2, 'train', Distance::miles("87"), 90, 1, 0, "45.00", "Adult off-peak return", 1),
                new OtherStageDefinition(3, 'walk', Distance::miles("1.5"), 35, 1, 0),
            ]),
            new JourneyDefinition(5, 'Piccadilly Circus, London', '4:00pm', 'Home', '6:55pm', 'Go home', [
                new OtherStageDefinition(1, 'walk', Distance::miles("1.5"), 40, 1, 0),
                new PublicStageDefinition(2, 'train', Distance::miles("87"), 90, 1, 0, '0', "Adult off-peak return", 1),
                new PrivateStageDefinition(3, 'car', Distance::miles("8"), 30, 1, 0, true,'0', 1),
            ]),

            // day 6
            new JourneyDefinition(6, 'Home', '10:05am', 'Bonnymead Park, Townton', '10:30am', 'To walk the dog', [
                new OtherStageDefinition(1, 'walk', Distance::miles("1"), 25, 1, 0),
            ]),
            new JourneyDefinition(6, 'Bonnymead Park, Townton', '11:15am', 'Home', '11:40am', 'Go home', [
                new OtherStageDefinition(1, 'walk', Distance::miles("1"), 25, 1, 0),
            ]),

            // day 7
            new JourneyDefinition(7, 'Home', '10:30am', 'Ocean village, Southampton', '11:50am', 'To go shoe shopping', [
                new PrivateStageDefinition(1, 'car', Distance::miles("8"), 30, 2, 0, true, "0", 0),
                new PublicStageDefinition(2, 'train', Distance::miles("25"), 40, 2, 0, "12.80", "Adult return", 1)
            ]),
            new JourneyDefinition(7, 'Ocean Village, Southampton', '2:35pm', 'Home', '3:50am', 'Go home', [
                new PublicStageDefinition(1, 'train', Distance::miles("25"), 45, 2, 0, '0', "Adult return", 1),
                new PrivateStageDefinition(2, 'car', Distance::miles("8"), 25, 2, 0, true, '0', 0),
            ]),
        ];
    }
}