<?php

namespace App\Tests\EventSubscriber;

use App\Entity\PropertyChangeLog;
use App\Entity\PropertyChangeLoggable;
use App\Entity\User;
use App\Repository\PropertyChangeLogRepository;
use App\Tests\DataFixtures\StageFixtures;
use App\Tests\Functional\AbstractWebTestCase;
use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Symfony\Component\PropertyAccess\PropertyAccess;

abstract class AbstractPropertyChangeLogTest extends AbstractWebTestCase
{
    protected EntityManagerInterface $entityManager;
    protected PropertyChangeLogRepository $propertyChangeLogRepository;
    protected ReferenceRepository $referenceRepository;

    public function setUp(): void
    {
        parent::setUp();

        $container = static::getContainer();
        $databaseTool = $container->get(DatabaseToolCollection::class)->get();
        $executor = $databaseTool->loadFixtures([
            StageFixtures::class
        ]);

        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->propertyChangeLogRepository = $this->entityManager->getRepository(PropertyChangeLog::class);
        $this->referenceRepository = $executor->getReferenceRepository();
    }

    abstract public function dataPropertyChangeLog(): array;

    /**
     * @dataProvider dataPropertyChangeLog
     */
    public function testPropertyChangeLog(
        string $userRef,
        string $refName,
        string $refClass,
        string $propertyPath,
        mixed  $value,
        bool   $changeLogIsExpected,
        string $expectedLoggedPath = null,
        mixed  $expectedLoggedValue = null,
    ): void
    {
        $user = $this->referenceRepository->getReference($userRef);
        $this->assertInstanceOf(User::class, $user);
        $this->client->loginUser($user);

        $this->assertEquals(0, $this->propertyChangeLogRepository->count([]), 'There should initially be no property change log entries');

        $entity = $this->referenceRepository->getReference($refName);

        $this->assertInstanceOf($refClass, $entity);
        $this->assertInstanceOf(PropertyChangeLoggable::class, $entity);

        if ($value instanceof \Closure) {
            // If we directly called, we'd be running the closure with the scope it had in dataPropertyLog
            // which would result in "entityManager not initialized". Here we run it with our current scope
            // and everything works.
            $value = $value->call($this, []);
        }

        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $propertyAccessor->setValue($entity, $propertyPath, $value);
        $this->entityManager->flush();

        $changeLogRecords = $this->propertyChangeLogRepository->findAll();

        $this->assertCount($changeLogIsExpected ? 1 : 0, $changeLogRecords, "Unexpected number of propertyChangeLog records");

        if ($changeLogIsExpected) {
            $changeLogRecord = $changeLogRecords[0];
            $this->assertInstanceOf(PropertyChangeLog::class, $changeLogRecord);

            $this->assertEquals($entity->getId(), $changeLogRecord->getEntityId());
            $this->assertEquals($refClass, $changeLogRecord->getEntityClass());
            $this->assertEquals($expectedLoggedPath ?? $propertyPath, $changeLogRecord->getPropertyName());
            $this->assertEquals($expectedLoggedValue ?? $value, $changeLogRecord->getPropertyValue());

            $interviewer = $user->getInterviewer();
            $this->assertEquals(
                $interviewer ? $interviewer->getSerialId() : null,
                $changeLogRecord->getInterviewerSerialId()
            );
        }
    }
}