<?php

namespace App\Tests\DataFixtures;

use App\DataFixtures\Definition\JourneyDefinition;
use App\DataFixtures\Definition\OtherStageDefinition;
use App\DataFixtures\Definition\PrivateStageDefinition;
use App\DataFixtures\Definition\PublicStageDefinition;
use App\DataFixtures\FixtureHelper;
use App\Entity\DiaryKeeper;
use App\Entity\Distance;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class JourneyFixtures extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @return JourneyDefinition[]|array
     */
    public static function getJourneyDefinitions(): array
    {
        return [
            'journey:1' => new JourneyDefinition(1, 'Home', '2020-01-01 8:26', 'Wobble', '2020-01-01 8:56', 'to-work', [
                'journey:1/stage:1'=> new PrivateStageDefinition(1, 'car', Distance::miles("30"), 30, 1, 0, true, '0', 'Red Tesla'),
            ]),
            'journey:2' => new JourneyDefinition(1, 'Wobble', '2020-01-01 16:00', 'Home', '2020-01-01 17:00', 'to-home', [
                'journey:2/stage:1'=> new PublicStageDefinition(1, 'bus-or-coach', Distance::miles("28"), 35, 1, 0, '3.50', 'standard-ticket', 1, 'London bus'),
                'journey:2/stage:2'=> new OtherStageDefinition(2, 'walk', Distance::miles("2"), 25, 1, 0),
            ]),
        ];
    }

    public static function getAdditionalJourneyDefinitions(): array
    {
        return [
            'journey:3' => new JourneyDefinition(7, 'Start', '2020-01-07 8:26', 'Finish', '2020-01-07 8:56', 'purpose', [
                'journey:3/stage:1'=> new PrivateStageDefinition(1, 'car', Distance::miles("30"), 30, 1, 1, true, '0', 'Red Tesla'),
            ]),
        ];
    }

    public static function getAllJourneyDefinitions(): array
    {
        return array_merge(self::getJourneyDefinitions(), self::getAdditionalJourneyDefinitions());
    }

    public function load(ObjectManager $manager): void
    {
        $diaryKeeper = $this->getReference('diary-keeper:adult');

        assert($diaryKeeper instanceof DiaryKeeper);

        foreach(self::getAllJourneyDefinitions() as $name => $definition) {
            $day = $diaryKeeper->getDiaryDayByNumber($definition->getDayNumber());
            $journey = FixtureHelper::createJourney($definition, $day);

            $this->addReference($name, $journey);
            $manager->persist($journey);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [UserFixtures::class];
    }
}

