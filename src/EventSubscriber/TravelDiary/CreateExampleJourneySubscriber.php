<?php

namespace App\EventSubscriber\TravelDiary;

use App\DataFixtures\Definition\JourneyDefinition;
use App\DataFixtures\FixtureHelper;
use App\Entity\DiaryDay;
use App\Entity\DiaryKeeper;
use App\Entity\Journey\Journey;
use App\Entity\Journey\Method;
use App\Utility\TravelDiary\PracticeDayExampleJourneyFixtures;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::prePersist)]
readonly class CreateExampleJourneySubscriber
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private HasUsedPracticeDaySubscriber $hasUsedPracticeDaySubscriber,
    ) {}

    public function prePersist(PrePersistEventArgs $eventArgs): void
    {
        $entity = $eventArgs->getObject();

        if ($entity instanceof DiaryDay && $entity->isPracticeDay()) {
            $this->hasUsedPracticeDaySubscriber->disable();
            foreach (PracticeDayExampleJourneyFixtures::getPracticeDayExampleJourneyFixtures() as $journeyDef)
            {
                $entity->addJourney($this->createAndPersistJourney($journeyDef, $entity->getDiaryKeeper()));
            }
            $entity->getDiaryKeeper()->setHasUsedPracticeDay(false);
        }
    }

    private function createAndPersistJourney(JourneyDefinition $journeyDef, DiaryKeeper $diaryKeeper): Journey
    {
        $this->entityManager->persist(
            $journey = FixtureHelper::createJourney($journeyDef, $diaryKeeper->getDiaryDayByNumber($journeyDef->getDayNumber()))
        );
        foreach ($journeyDef->getStageDefinitions() as $stageDef) {
            $this->entityManager->persist(
                $stage = FixtureHelper::createStage($stageDef, $this->entityManager->getRepository(Method::class), $diaryKeeper->getHousehold()->getVehicles()->toArray())
            );
            $journey->addStage($stage);
        }
        return $journey;
    }

}
