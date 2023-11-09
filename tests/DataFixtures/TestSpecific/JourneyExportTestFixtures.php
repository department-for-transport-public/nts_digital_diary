<?php

namespace App\Tests\DataFixtures\TestSpecific;

use App\DataFixtures\Definition\JourneyDefinition;
use App\DataFixtures\Definition\OtherStageDefinition;
use App\DataFixtures\Definition\PublicStageDefinition;
use App\DataFixtures\FixtureHelper;
use App\Entity\DiaryKeeper;
use App\Entity\Embeddable\Distance;
use App\Entity\Journey\Journey;
use App\Entity\Journey\Method;
use App\Entity\Vehicle;
use App\Tests\DataFixtures\UserFixtures;
use App\Tests\DataFixtures\VehicleFixtures;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class JourneyExportTestFixtures extends AbstractFixture implements DependentFixtureInterface
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
            'journey:1' => new JourneyDefinition(1, 'Wobble', '2020-01-01 16:00', 'Home', '2020-01-01 16:40', Journey::TO_GO_HOME, [
                'journey:1/stage:1'=> new PublicStageDefinition(1, 'bus-or-coach', Distance::miles("28"), 35, 1, 0, '3.50', 'standard-ticket', 1, 'London bus'),
            ]),
            'journey:2' => new JourneyDefinition(1, 'Home', '2020-01-01 18:00', 'Puddle', '2020-01-01 18:30', 'shopping', [
                'journey:2/stage:1'=> new PublicStageDefinition(1, 'bus-or-coach', Distance::miles("26"), 20, 1, 0, '3.50', 'standard-ticket', 1, 'London bus'),
                'journey:2/stage:2'=> new OtherStageDefinition(2, 'walk', Distance::miles("2"), 25, 1, 0),
            ]),
        ];
    }
}