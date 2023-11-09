<?php

namespace App\Tests\Functional\DiaryKeeper;

use App\Entity\Journey\Journey;
use App\Tests\Functional\Wizard\Action\DatabaseTestCaseInterface;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class SharedJourneyDatabaseTestCase implements DatabaseTestCaseInterface
{
    public function __construct(protected $sourceJourneyId, protected array $sharedWith = []) {}

    public function checkDatabaseAsExpected(EntityManagerInterface $entityManager, TestCase $testCase): void
    {
        $sourceJourney = $entityManager->find(Journey::class, $this->sourceJourneyId);

        $testCase::assertEquals(count($this->sharedWith), $sourceJourney?->getSharedTo()->count());

        foreach ($sourceJourney->getSharedTo() as $sharedJourney) {
            unset($this->sharedWith[array_search($sharedJourney->getDiaryDay()->getDiaryKeeper()->getId(), $this->sharedWith)]);

            $pa = new PropertyAccessor();
            $propertyPaths = ['diaryDay.number', 'startTime', 'endTime', 'startLocation', 'endLocation', 'stages.count'];
            foreach ($propertyPaths as $propertyPath) {
                $testCase::assertEquals(
                    $pa->getValue($sourceJourney, $propertyPath),
                    $pa->getValue($sharedJourney, $propertyPath),
                    "Property: $propertyPath"
                );
            }
        }

        $testCase::assertEmpty($this->sharedWith);
    }
}