<?php

namespace App\Tests\FormWizard;

use App\Entity\Journey\Journey;
use App\Tests\DataFixtures\StageFixtures;
use App\Tests\Functional\AbstractWebTestCase;
use Doctrine\ORM\EntityManager;
use Ghost\GovUkCoreBundle\FormWizard\PropertyMerger;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;

class PropertyMergerTest extends AbstractWebTestCase
{
    public function testCollections(): void
    {
        $databaseTool = static::getContainer()->get(DatabaseToolCollection::class)->get();
        $fixtures = $databaseTool->loadFixtures([
            StageFixtures::class
        ]);
        $fixtureReferenceRepository = $fixtures->getReferenceRepository();

        /** @var EntityManager $entityManager */
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $sourceJourney = $entityManager->createQueryBuilder()
            ->select('j, s')
            ->from(Journey::class, 'j')
            ->leftJoin('j.stages', 's')
            ->where('j.id = :id')
            ->setParameter('id', $fixtureReferenceRepository->getReference('journey:2', Journey::class)->getId())
            ->getQuery()
            ->getOneOrNullResult();

        // clear the entity manager
        $entityManager->clear();

        // assert that the stages are not managed
        foreach ($sourceJourney->getStages() as $stage) {
            self::assertFalse($entityManager->contains($stage));
        }

        // merge the properties to a new instance of the class
        $targetJourney = new Journey();

        /** @var PropertyMerger $propertyMerger */
        $propertyMerger = static::getContainer()->get(PropertyMerger::class);
        $propertyMerger->merge($targetJourney, $sourceJourney, ['stages']);

        // check the properties are the same, and managed by EM
        foreach ($targetJourney->getStages() as $key => $stage) {
            self::assertTrue($entityManager->contains($stage));
            self::assertSame($sourceJourney->getStages()->get($key)->getId(), $stage->getId());
        }
    }
}