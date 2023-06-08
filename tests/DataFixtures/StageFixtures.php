<?php

namespace App\Tests\DataFixtures;

use App\DataFixtures\FixtureHelper;
use App\Entity\Journey\Journey;
use App\Entity\Journey\Method;
use App\Entity\Vehicle;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class StageFixtures extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $methodRepo = $manager->getRepository(Method::class);
        $vehicleRepo = $manager->getRepository(Vehicle::class);

        foreach(JourneyFixtures::getAllJourneyDefinitions() as $name => $definition) {
            /** @var Journey $journey */
            $journey = $this->getReference($name);

            $household = $journey->getDiaryDay()->getDiaryKeeper()->getHousehold();
            $vehicles = $vehicleRepo->createQueryBuilder('v', 'v.friendlyName')
                ->where('v.household = :household')
                ->setParameter('household', $household)
                ->getQuery()
                ->execute();

            foreach($definition->getStageDefinitions() as $stageName => $stageDefinition) {
                $stage = FixtureHelper::createStage($stageDefinition, $methodRepo, $vehicles);
                $journey->addStage($stage);
                $this->addReference($stageName, $stage);
                $manager->persist($stage);
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [JourneyFixtures::class, VehicleFixtures::class];
    }
}

