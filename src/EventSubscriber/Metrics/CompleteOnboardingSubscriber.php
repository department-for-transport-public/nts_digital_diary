<?php

namespace App\EventSubscriber\Metrics;

use App\Event\CompleteOnboardingEvent;
use App\Utility\Metrics\Events\OnboardingCompleteEvent;
use App\Utility\Metrics\MetricsHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CompleteOnboardingSubscriber implements EventSubscriberInterface
{
    public function __construct(protected MetricsHelper $metrics) {}

    public static function getSubscribedEvents(): array
    {
        return [
            CompleteOnboardingEvent::class => 'onCompleteOnboarding',
        ];
    }

    public function onCompleteOnboarding(CompleteOnboardingEvent $event): void
    {
        if ($event->getHousehold()->getAreaPeriod()->getTrainingInterviewer()) {
            return;
        }
        $this->metrics->log(new OnboardingCompleteEvent($event->getHousehold()->getSerialNumber(null, ...MetricsHelper::GET_SERIAL_METHOD_ARGS)));
    }
}