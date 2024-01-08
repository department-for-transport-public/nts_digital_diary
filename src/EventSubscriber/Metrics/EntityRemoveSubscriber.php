<?php

namespace App\EventSubscriber\Metrics;

use App\Entity\Journey\Journey;
use App\Entity\Journey\Stage;
use App\Utility\Metrics\Events\Entity\JourneyDeleteEvent;
use App\Utility\Metrics\Events\Entity\StageDeleteEvent;
use App\Utility\Metrics\Events\Entity\StageEditEvent;
use App\Utility\Metrics\MetricsHelper;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Events;

class EntityRemoveSubscriber implements EventSubscriber
{
    protected array $objectIdMap = [];

    public function __construct(protected MetricsHelper $metrics){}

    public function preRemove(PreRemoveEventArgs $eventArgs): void
    {
        $object = $eventArgs->getObject();
        $this->objectIdMap[spl_object_id($object)] = $object->getId();
        if ($object instanceof Journey) {
            $this->metrics->ignoreEvents([StageDeleteEvent::class]);
        }
        if ($object instanceof Stage) {
            $this->metrics->ignoreEvents([StageEditEvent::class]);
        }
    }

    public function postRemove(PostRemoveEventArgs $eventArgs): void
    {
        $object = $eventArgs->getObject();
        $originalId = $this->objectIdMap[spl_object_id($object)];
        if ($object instanceof Journey) {
            if (!$object->getDiaryDay()?->getDiaryKeeper()?->getHousehold()?->getAreaPeriod()) {
                return;
            }
            $this->metrics->log(new JourneyDeleteEvent($object, $originalId));
        }
        if ($object instanceof Stage) {
            if (!$object->getJourney()?->getDiaryDay()?->getDiaryKeeper()?->getHousehold()?->getAreaPeriod()) {
                return;
            }
            $this->metrics->log(new StageDeleteEvent($object, $originalId));
        }
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::postRemove,
            Events::preRemove,
        ];
    }
}