<?php

namespace App\EventSubscriber\TravelDiary;

use App\Entity\Journey\Journey;
use App\Entity\Journey\Stage;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::prePersist)]
#[AsDoctrineListener(event: Events::preRemove)]
#[AsDoctrineListener(event: Events::preUpdate)]
class HasUsedPracticeDaySubscriber
{
    private bool $enabled = true;

    public function disable(): void
    {
        $this->enabled = false;
    }

    public function enable(): void
    {
        $this->enabled = true;
    }

    public function preRemove(PreRemoveEventArgs $eventArgs): void
    {
        $this->entityChanged($eventArgs->getObject());
    }

    public function preUpdate(PreUpdateEventArgs $eventArgs): void
    {
        $this->entityChanged($eventArgs->getObject());
    }

    public function prePersist(PrePersistEventArgs $eventArgs): void
    {
        $this->entityChanged($eventArgs->getObject());
    }

    private function entityChanged($entity): void
    {
        if (!$this->enabled) {
            return;
        }

        switch(true) {
            case $entity instanceof Journey:
                $this->logChangesToPracticeDay($entity);
                break;
            case $entity instanceof Stage:
                $this->logChangesToPracticeDay($entity->getJourney());
                break;
        };
    }

    private function logChangesToPracticeDay(Journey $journey): void
    {
        $diaryDay = $journey->getDiaryDay();
        $diaryKeeper = $diaryDay?->getDiaryKeeper();

        if (!$diaryDay->isPracticeDay()) {
            return;
        }

        $diaryKeeper->setHasUsedPracticeDay(true);
    }
}
