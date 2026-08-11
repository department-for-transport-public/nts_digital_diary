<?php

namespace App\EventSubscriber\Onboarding;

use App\Entity\AreaPeriod;
use App\Utility\TravelDiary\AreaPeriodHelper;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::prePersist)]
class CreateOnboardingCodesSubscriber
{

    public function __construct(protected readonly AreaPeriodHelper $areaPeriodHelper)
    {}

    public function prePersist(PrePersistEventArgs $eventArgs): void
    {
        $entity = $eventArgs->getObject();

        if (!$entity instanceof AreaPeriod) {
            return;
        }

        // Do not create codes for training areas
        if ($entity->isTrainingArea()) {
            return;
        }

        $this->areaPeriodHelper->createCodesForArea($entity, false);
    }
}
