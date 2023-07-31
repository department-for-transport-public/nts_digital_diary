<?php

namespace App\Tests\DataFixtures\TestSpecific;

use App\DataFixtures\Definition\JourneyDefinition;
use App\DataFixtures\Definition\OtherStageDefinition;
use App\DataFixtures\Definition\PrivateStageDefinition;
use App\DataFixtures\Definition\PublicStageDefinition;
use App\DataFixtures\FixtureHelper;
use App\Entity\DiaryKeeper;
use App\Entity\Distance;
use App\Entity\Journey\Method;
use App\Entity\Vehicle;
use App\Tests\DataFixtures\UserFixtures;
use App\Tests\DataFixtures\VehicleFixtures;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class SplitJourneyTestFixtures extends AbstractFixture implements DependentFixtureInterface
{
    public function getDependencies(): array
    {
        return [UserFixtures::class, VehicleFixtures::class];
    }

    public function load(ObjectManager $manager): void
    {
        $methodRepo = $manager->getRepository(Method::class);
        $vehicleRepo = $manager->getRepository(Vehicle::class);

        $diaryKeeper = $this->getReference('diary-keeper:adult');
        assert($diaryKeeper instanceof DiaryKeeper);

        $household = $diaryKeeper->getHousehold();
        $vehicles = $vehicleRepo->createQueryBuilder('v', 'v.friendlyName')
            ->where('v.household = :household')
            ->setParameter('household', $household)
            ->getQuery()
            ->execute();

        foreach(self::getJourneyDefinitions() as $name => $definition) {
            $day = $diaryKeeper->getDiaryDayByNumber($definition->getDayNumber());
            $journey = FixtureHelper::createJourney($definition, $day);

            $this->addReference($name, $journey);
            $manager->persist($journey);

            foreach($definition->getStageDefinitions() as $stageName => $stageDefinition) {
                $stage = FixtureHelper::createStage($stageDefinition, $methodRepo, $vehicles);
                $journey->addStage($stage);
                $this->addReference($stageName, $stage);
                $manager->persist($stage);
            }
        }

        $manager->flush();
    }

    public static function getJourneyDefinitions(): array
    {
        return [
            // Splittable journeys
            // -------------------

            // 1) simple home to home
            'journey:1' => new JourneyDefinition(1, 'Home', '2020-01-01 16:00', 'Home', '2020-01-01 17:00', 'walk the dog', [
                'journey:1/stage:1'=> new OtherStageDefinition(1, 'walk', Distance::miles("10"), 10, 1, 0),
            ]),

            // 2) simple non-home to non-home
            'journey:2' => new JourneyDefinition(1, 'Wobble', '2020-01-01 17:00', 'Wobble', '2020-01-01 18:00', 'shopping', [
                'journey:2/stage:1'=> new OtherStageDefinition(1, 'walk', Distance::miles("10"), 10, 1, 0),
            ]),

            // 3) 2-minute journey
            'journey:3' => new JourneyDefinition(1, 'Home', '2020-01-01 18:00', 'Home', '2020-01-01 18:02', 'walk the dog', [
                'journey:3/stage:1'=> new OtherStageDefinition(1, 'walk', Distance::miles("1"), 2, 1, 0),
            ]),

            // 4) Journey whose midpoint would have crossed a day boundary (with resultant midpoint still being within days 1-7)
            'journey:4' => new JourneyDefinition(1, 'Home', '2020-01-01 23:45', 'Home', '2020-02-01 00:15', 'walk the dog', [
                'journey:4/stage:1'=> new OtherStageDefinition(1, 'walk', Distance::miles("2"), 30, 1, 0),
            ]),


            // Not splittable
            // --------------

            // 5) Journey with different start and end locations
            'journey:5' => new JourneyDefinition(1, 'Home', '2020-01-01 19:00', 'Wobble', '2020-01-01 20:00', 'walk the dog', [
                'journey:5/stage:1'=> new OtherStageDefinition(1, 'walk', Distance::miles("10"), 60, 1, 0),
            ]),

            // 6) 1-minute journey: Cannot have a journey with the same start and end time (as one of the split journeys would end up being, if we tried this!)
            'journey:6' => new JourneyDefinition(1, 'Home', '2020-01-01 20:00', 'Home', '2020-01-01 20:01', 'walk the dog', [
                'journey:6/stage:1'=> new OtherStageDefinition(1, 'walk', Distance::miles("10"), 1, 1, 0),
            ]),

            // 7) Journey whose midpoint would have crossed a day boundary on day 7, so that resulting split return journey would be in day 8(!)
            'journey:7' => new JourneyDefinition(7, 'Home', '2020-01-01 23:45', 'Home', '2020-02-01 00:15', 'walk the dog', [
                'journey:7/stage:1'=> new OtherStageDefinition(1, 'walk', Distance::miles("2"), 30, 1, 0),
            ]),

            // 8) Public method
            'journey:8' => new JourneyDefinition(1, 'Home', '2020-01-01 21:00', 'Home', '2020-01-01 22:00', 'walk the dog', [
                'journey:8/stage:1' => new PublicStageDefinition(4, 'bus-or-coach', Distance::miles("28"), 35, 1, 0, null, 'standard-ticket', 1, 'London bus'),
            ]),

            // 9) Private method
            'journey:9' => new JourneyDefinition(1, 'Home', '2020-01-01 22:00', 'Home', '2020-01-01 22:30', 'walk the dog', [
                'journey:9/stage:1' => new PrivateStageDefinition(5, 'car', Distance::miles("30"), 30, 1, 0, true, null, 'Red Tesla'),
            ]),

            // 10) No stages
            'journey:10' => new JourneyDefinition(1, 'Home', '2020-01-01 22:30', 'Home', '2020-01-01 23:00', 'walk the dog', []),
        ];
    }
}