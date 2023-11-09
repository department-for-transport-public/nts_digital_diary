<?php

namespace App\Tests\DataFixtures\TestSpecific;

use App\DataFixtures\Definition\JourneyDefinition;
use App\DataFixtures\Definition\OtherStageDefinition;
use App\DataFixtures\Definition\PrivateStageDefinition;
use App\DataFixtures\Definition\PublicStageDefinition;
use App\DataFixtures\FixtureHelper;
use App\Entity\DiaryKeeper;
use App\Entity\Embeddable\Distance;
use App\Entity\Journey\Method;
use App\Entity\User;
use App\Entity\Vehicle;
use App\Tests\DataFixtures\StageFixtures;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ShareJourneyTestFixtures extends AbstractFixture implements DependentFixtureInterface
{
    public function getDependencies(): array
    {
        return [StageFixtures::class];
    }

    public function load(ObjectManager $manager)
    {
        /** @var DiaryKeeper $sourceDiaryKeeper */
        $sourceDiaryKeeper = $this->getReference('diary-keeper:adult');
        $household = $sourceDiaryKeeper->getHousehold();
        $vehicleRepo = $manager->getRepository(Vehicle::class);
        $methodRepo = $manager->getRepository(Method::class);
        $vehicles = $vehicleRepo->createQueryBuilder('v', 'v.friendlyName')
            ->where('v.household = :household')
            ->setParameter('household', $household)
            ->getQuery()
            ->execute();

        $extraDK1 = (new DiaryKeeper())
            ->setName('Extra Diary Keeper adult (Proxied)')
            ->setIsAdult(true)
            ->setNumber(5)
            ->setMediaType(DiaryKeeper::MEDIA_TYPE_DIGITAL)
            ->setUser((new User())->setUsername(User::generateNoLoginPlaceholder()))
            ->addProxy($sourceDiaryKeeper)
            ->setHousehold($household);
        $manager->persist($extraDK1);
        $this->addReference('diary-keeper:journey-share:adult', $extraDK1);

        $extraDK2 = (new DiaryKeeper())
            ->setName('Extra Diary Keeper child (Proxied)')
            ->setIsAdult(false)
            ->setNumber(6)
            ->setMediaType(DiaryKeeper::MEDIA_TYPE_DIGITAL)
            ->setUser((new User())->setUsername(User::generateNoLoginPlaceholder()))
            ->addProxy($sourceDiaryKeeper)
            ->setHousehold($household);
        $manager->persist($extraDK2);
        $this->addReference('diary-keeper:journey-share:child', $extraDK2);

        foreach($this->getJourneyFixtures() as $name => $definition) {
            $day = $sourceDiaryKeeper->getDiaryDayByNumber($definition->getDayNumber());
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

    private function getJourneyFixtures(): array
    {
        return [
            'sharing-journey:1' => new JourneyDefinition(6, 'Wobble', '19:00', 'Wabble', '20:00', 'visit friends', [
                'sharing-journey:1/stage:1'=> new PublicStageDefinition(1, 'bus-or-coach', Distance::miles("28"), 35, 2, 1, 350, 'standard-ticket', 1, 'London bus'),
                'sharing-journey:1/stage:2'=> new OtherStageDefinition(2, 'walk', Distance::miles("2"), 25, 2, 1),
                'sharing-journey:1/stage:3'=> new PrivateStageDefinition(3, 'car', Distance::miles("30"), 30, 2, 1, true, 0, 'Blue Golf'),
                'sharing-journey:1/stage:4'=> new PrivateStageDefinition(4, 'car', Distance::miles("30"), 30, 2, 1, false, 0, 'Red Tesla'),
            ]),
            'sharing-journey:2' => new JourneyDefinition(6, 'Wabble', '20:00', 'Home', '21:00', 'to-home', [
                'sharing-journey:2/stage:1'=> new PublicStageDefinition(1, 'bus-or-coach', Distance::miles("50"), 50, 2, 1, 350, 'standard-ticket', 1, 'London bus'),
            ]),
        ];
    }
}