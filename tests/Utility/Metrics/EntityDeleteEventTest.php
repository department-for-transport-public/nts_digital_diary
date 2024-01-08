<?php

namespace App\Tests\Utility\Metrics;

use App\Entity\Utility\MetricsLog;
use App\Repository\Utility\MetricsLogRepository;
use App\Tests\AbstractFixturesTest;
use App\Tests\DataFixtures\StageFixtures;
use Doctrine\ORM\EntityManagerInterface;

class EntityDeleteEventTest extends AbstractFixturesTest
{
    protected EntityManagerInterface $entityManager;
    protected MetricsLogRepository $metricsRepository;

    protected function setUp(): void
    {
        $this->bootKernelAndLoadFixtures([StageFixtures::class]);
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->metricsRepository = self::getContainer()->get(MetricsLogRepository::class);
        // Remove all logs left by fixtures
        $this->entityManager->createQueryBuilder()->delete(MetricsLog::class, 'm')->getQuery()->execute();
    }

    public function dataIgnoreOtherEventsWhenDeletingEntity(): array
    {
        return [
            ['journey:2'],
            ['journey:2/stage:1'],
        ];
    }

    /**
     * @dataProvider dataIgnoreOtherEventsWhenDeletingEntity
     */
    public function testIgnoreOtherEventsWhenDeletingEntity($fixtureRef): void
    {
        // delete journey with stages
        $entity = $this->getFixtureByReference($fixtureRef);
        $entityId = $entity->getId();

        $this->entityManager->remove($entity);
        $this->entityManager->flush();

        // ensure only single metric log added (for journey)
        $metrics = $this->metricsRepository->findAll();
        self::assertCount(1, $metrics);
        self::assertEquals($entityId, $metrics[0]->getMetadata()['id']);
    }
}