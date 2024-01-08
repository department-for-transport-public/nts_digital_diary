<?php

namespace App\EventSubscriber;

use App\Entity\AreaPeriod;
use App\Utility\TravelDiary\AreaPeriodHelper;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;

class CreateOnboardingCodesSubscriber implements EventSubscriber
{

    private AreaPeriodHelper $areaPeriodHelper;

    public function __construct(AreaPeriodHelper $areaPeriodHelper)
    {
        $this->areaPeriodHelper = $areaPeriodHelper;
    }

    public function prePersist(LifecycleEventArgs $eventArgs)
    {
        $entity = $eventArgs->getEntity();

        if (!$entity instanceof AreaPeriod) {
            return;
        }

        // Do not create codes for training areas
        if ($entity->getTrainingInterviewer()) {
            return;
        }

        $this->areaPeriodHelper->createCodesForArea($entity, false);
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::prePersist
        ];
    }
}