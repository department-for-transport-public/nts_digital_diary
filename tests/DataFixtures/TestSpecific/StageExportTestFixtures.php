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

class StageExportTestFixtures extends AbstractFixture implements DependentFixtureInterface
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

        foreach ($household->getDiaryKeepers() as $diaryKeeper) {
            $diaryKeeper->setDiaryState(DiaryKeeper::STATE_APPROVED);
        }

        $household->setSubmittedAt(new \DateTime());
        $household->setSubmittedBy('Test');

        $manager->flush();
    }

    public static function getJourneyDefinitions(): array
    {
        return [
            'journey:1' => new JourneyDefinition(1, 'Wobble', '2020-01-01 16:00', 'Home', '2020-01-01 17:00', 'to-home', [
                'journey:1/stage:1'=> new PublicStageDefinition(1, 'bus-or-coach', Distance::miles("28"), 35, 1, 0, '3.50', 'standard-ticket', 1, 'London bus'),
                'journey:1/stage:2'=> new OtherStageDefinition(2, 'walk', Distance::miles("2"), 25, 1, 0),
                'journey:1/stage:3'=> new PrivateStageDefinition(3, 'car', Distance::miles("30"), 30, 1, 0, true, '0', 'Red Tesla'),

                // Stages with empty cost
                'journey:1/stage:4'=> new PublicStageDefinition(4, 'bus-or-coach', Distance::miles("28"), 35, 1, 0, null, 'standard-ticket', 1, 'London bus'),
                'journey:1/stage:5'=> new PrivateStageDefinition(5, 'car', Distance::miles("30"), 30, 1, 0, true, null, 'Red Tesla'),

                // stages with no cost
                'journey:1/stage:6'=> new PublicStageDefinition(6, 'bus-or-coach', Distance::miles("28"), 35, 1, 0, '0', 'standard-ticket', 1, 'London bus'),
                'journey:1/stage:7'=> new PrivateStageDefinition(7, 'car', Distance::miles("30"), 30, 1, 0, true, '0', 'Red Tesla'),
            ]),
        ];
    }
}