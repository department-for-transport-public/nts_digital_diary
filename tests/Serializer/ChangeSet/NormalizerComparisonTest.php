<?php

namespace App\Tests\Serializer\ChangeSet;

use App\Entity\Journey\Journey;
use App\Entity\PropertyChangeLog;
use App\Tests\DataFixtures\JourneyFixtures;
use App\Tests\DataFixtures\StageFixtures;
use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\Authentication\Token\NullToken;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Serializer;

class NormalizerComparisonTest extends WebTestCase
{
    private ReferenceRepository $fixtureReferenceRepository;
    private EntityManager $entityManager;
    private Serializer $normalizer;

    protected function setUp(): void
    {
        $container = static::getContainer();

        $securityMock = $this->getMockBuilder(Security::class)
            ->disableOriginalConstructor()
            ->getMock();
        // The mocked security service is to ensure that the property change log works
//        $securityMock = $this->createMock(Security::class);
        $securityMock->method('getToken')->willReturn(new NullToken());
        $container->set(Security::class, $securityMock);

        $databaseTool = $container->get(DatabaseToolCollection::class)->get();
        $fixtures = $databaseTool->loadFixtures([JourneyFixtures::class, StageFixtures::class]);

        $this->fixtureReferenceRepository = $fixtures->getReferenceRepository();
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->normalizer = $container->get(NormalizerInterface::class);
    }

    public function testJourney()
    {
        $this->compareNormalizerWithChangeLog(
            $this->getJourney(),
            ['id', 'sharedFromId', 'purposeCode', 'stages', '_history']
        );
    }

    public function testStage()
    {
        $this->compareNormalizerWithChangeLog(
            $this->getJourney()->getStageByNumber(1),
            ['methodCode', 'methodOther', 'vehicleCapiNumber', '_history'],
            ['parkingHasCost', 'ticketHasCost']
        );
    }

    protected function compareNormalizerWithChangeLog(
        $entity,
        array $ignoreNormalizedProperties = [],
        array $ignorePropertyChangeLogProperties = [],
    ): void {
        $normalizedProperties = array_diff(
            array_keys($this->normalizer->normalize($entity, null, ['apiVersion' => 1])),
            $ignoreNormalizedProperties
        );

        $changeLogChanges = $this->entityManager->getRepository(PropertyChangeLog::class)
            ->findBy(['entityId' => $entity->getId()]);

        $changeLogProperties = array_diff(
            array_map(fn(PropertyChangeLog $l) => $l->getPropertyName(), $changeLogChanges),
            $ignorePropertyChangeLogProperties
        );

        $this->assertArraysSameValues($normalizedProperties, $changeLogProperties);
    }

    protected function assertArraysSameValues($expected, $actual, $message = 'Missing (-) and excess (+)')
    {
        $missing = array_diff($expected, $actual);
        $excess = array_diff($actual, $expected);

        $this->assertEquals($missing, $excess, $message);
    }

    protected function getJourney(): Journey
    {
        return $this->fixtureReferenceRepository->getReference('journey:2');
    }
}