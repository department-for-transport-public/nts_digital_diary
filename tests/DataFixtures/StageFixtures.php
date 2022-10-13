<?php

namespace App\Tests\DataFixtures;

use App\Entity\Journey\Journey;
use App\Entity\Journey\Method;
use App\Entity\Journey\Stage;
use App\Entity\Vehicle;
use App\Repository\Journey\MethodRepository;
use App\Tests\Definition\PrivateStageDefinition;
use App\Tests\Definition\PublicStageDefinition;
use App\Tests\Definition\StageDefinition;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class StageFixtures extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $methodRepo = $manager->getRepository(Method::class);
        $vehicleRepo = $manager->getRepository(Vehicle::class);

        foreach(JourneyFixtures::getJourneyDefinitions() as $name => $definition) {
            /** @var Journey $journey */
            $journey = $this->getReference($name);

            $household = $journey->getDiaryDay()->getDiaryKeeper()->getHousehold();
            $vehicles = $vehicleRepo->createQueryBuilder('v', 'v.friendlyName')
                ->where('v.household = :household')
                ->setParameter('household', $household)
                ->getQuery()
                ->execute();

            foreach($definition->getStageDefinitions() as $stageName => $stageDefinition) {
                $stage = $this->createStage($stageDefinition, $methodRepo, $vehicles);
                $journey->addStage($stage);
                $this->addReference($stageName, $stage);
                $manager->persist($stage);
            }
        }

        $manager->flush();
    }

    protected function createStage(StageDefinition $definition, MethodRepository $methodRepository, array $vehicles): Stage {
        $method = $methodRepository->findOneBy(['descriptionTranslationKey' => $definition->getMethod()]);

        if (!$method) {
            throw new \RuntimeException("Invalid method: {$definition->getMethod()}");
        }

        $stage = (new Stage())
            ->setNumber($definition->getNumber())
            ->setMethod($method)
            ->setDistanceTravelled($definition->getDistance())
            ->setTravelTime($definition->getTravelTime())
            ->setAdultCount($definition->getAdultCount())
            ->setChildCount($definition->getChildCount());

        switch($method->getType() ?? null) {
            case Method::TYPE_PUBLIC:
                if (!$definition instanceof PublicStageDefinition) throw new \RuntimeException('invalid type');

                $stage
                    ->setTicketType($definition->getTicketType())
                    ->setTicketCost($definition->getTicketCost())
                    ->setBoardingCount($definition->getBoardingCount());
                break;

            case Method::TYPE_PRIVATE:
                if (!$definition instanceof PrivateStageDefinition) throw new \RuntimeException('invalid type');
                $vehicle = $vehicles[$definition->getVehicle()] ?? null;

                if ($vehicle) {
                    $stage
                        ->setVehicle($vehicle)
                        ->setVehicleOther(null);
                } else {
                    $stage
                        ->setVehicle(null)
                        ->setVehicleOther($definition->getVehicle());
                }

                $stage
                    ->setIsDriver($definition->getIsDriver())
                    ->setParkingCost($definition->getParkingCost());
        }

        return $stage;
    }

    public function getDependencies(): array
    {
        return [JourneyFixtures::class, VehicleFixtures::class];
    }
}

