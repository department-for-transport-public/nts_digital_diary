<?php

namespace App\Tests\DataFixtures;

use App\DataFixtures\NtsFixtures;
use App\Entity\Household;
use App\Entity\Journey\Method;
use App\Entity\Vehicle;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class VehicleFixtures extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $methodRepo = $manager->getRepository(Method::class);
        $method = $methodRepo->findOneBy(['descriptionTranslationKey' => 'van-or-lorry']);

        /** @var Household $household */
        $household = $this->getReference('household:onboarded');

        $vehicle = (new Vehicle())
            ->setMethod($method)
            ->setFriendlyName('A-Team van')
            ->setHousehold($household);

        $this->addReference('vehicle:1', $vehicle);

        $household->addVehicle($vehicle);

        $manager->persist($vehicle);
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [HouseholdFixtures::class, NtsFixtures::class];
    }
}