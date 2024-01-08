<?php

namespace App\EventSubscriber\Metrics;

use App\Event\VideoJsEvent;
use App\Utility\Metrics\Events\VideoEvent as VideoMetricEvent;
use App\Utility\Metrics\MetricsHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class VideoActionSubscriber implements EventSubscriberInterface
{
    public function __construct(protected MetricsHelper $metrics) {}

    public function videoEvent(VideoJsEvent $event): void {
        $additionalData = $event->getAdditionalData();
        if ($event->getType() === VideoJsEvent::TYPE_PLAY && ($additionalData['seconds'] ?? false) !== "0") {
            // don't log play events that don't start at the beginning (or don't have a timestamp)
            return;
        }
        $this->metrics->log(new VideoMetricEvent($event->getType(), $event->getVideoId(), $event->getUrlPath()));
    }

    public static function getSubscribedEvents(): array
    {
        return [VideoJsEvent::class => 'videoEvent'];
    }
}