<?php

namespace App\Tests\DataFixtures;

use App\Entity\DiaryDay;
use App\Entity\DiaryKeeper;
use App\Entity\Distance;
use App\Entity\Journey\Journey;
use App\Tests\Definition\JourneyDefinition;
use App\Tests\Definition\OtherStageDefinition;
use App\Tests\Definition\PrivateStageDefinition;
use App\Tests\Definition\PublicStageDefinition;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PropertyAccess\PropertyAccess;

class JourneyFixtures extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @return JourneyDefinition[]|array
     */
    public static function getJourneyDefinitions(): array
    {
        return [
            'journey:1' => new JourneyDefinition('Home', '2020-01-01 8:26', 'Wobble', '2020-01-01 8:56', 'to-work', [
                'journey:1/stage:1'=> new PrivateStageDefinition(1, 'car', Distance::miles(30), 30, 1, 0, true, null, 'Red Tesla'),
            ]),
            'journey:2' => new JourneyDefinition('Wobble', '2020-01-01 16:00', 'Home', '2020-01-01 17:00', 'to-home', [
                'journey:2/stage:1'=> new PublicStageDefinition(1, 'bus-or-coach', Distance::miles(28), 35, 1, 0, 350, 'standard-ticket', 1),
                'journey:2/stage:2'=> new OtherStageDefinition(2, 'walk', Distance::miles(2), 25, 1, 0),
            ]),
        ];
    }

    public function load(ObjectManager $manager): void
    {
        $diaryKeeper = $this->getReference('diary-keeper:adult');

        assert($diaryKeeper instanceof DiaryKeeper);

        $day = $diaryKeeper->getDiaryDayByNumber(1);

        foreach(self::getJourneyDefinitions() as $name => $definition) {
            $journey = $this->createJourney($definition, $day);

            $this->addReference($name, $journey);
            $manager->persist($journey);
        }

        $manager->flush();
    }

    protected function createJourney(JourneyDefinition $definition, DiaryDay $day): Journey
    {
        $journey = (new Journey())
            ->setStartTime(new \DateTime($definition->getStartTime()))
            ->setEndTime(new \DateTime($definition->getEndTime()))
            ->setDiaryDay($day)
            ->setPurpose($definition->getPurpose());

        $this->setHomeAndLocation($journey, 'Start', $definition);
        $this->setHomeAndLocation($journey, 'End', $definition);

        return $journey;
    }

    protected function setHomeAndLocation(Journey $journey, string $prefix, JourneyDefinition $definition): void {
        $accessor = PropertyAccess::createPropertyAccessor();
        $location = $accessor->getValue($definition, "{$prefix}Location");

        $accessor->setValue($journey, "Is{$prefix}Home", $location === 'Home');
        $accessor->setValue($journey, "{$prefix}Location", $location === 'Home' ? null : $location);
    }

    public function getDependencies(): array
    {
        return [UserFixtures::class];
    }
}

