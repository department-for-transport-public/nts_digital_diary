<?php

namespace App\EventSubscriber\Metrics;

use App\Event\CompleteOnboardingEvent;

use App\Utility\Metrics\Events\OnboardingCompleteEvent as OnboardingCompleteMetricsEvent;
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
        $areaPeriod = $event->getHousehold()->getAreaPeriod();

        if (!$areaPeriod || $areaPeriod->getTrainingInterviewer()) {
            return;
        }

        $metricsEvent = new OnboardingCompleteMetricsEvent(
            $event->getHousehold()->getSerialNumber(null, ...MetricsHelper::GET_SERIAL_METHOD_ARGS),
            $event->getOnboardingType(),
        );

        $this->metrics->log($metricsEvent);
    }
}
