<?php

namespace App\EventSubscriber\Metrics;

use App\Entity\DiaryKeeper;
use App\Utility\Metrics\Events\DiaryStateEvent;
use App\Utility\Metrics\MetricsHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\CompletedEvent;
use Symfony\Component\Workflow\Event\LeaveEvent;

class DiaryStateChangeSubscriber implements EventSubscriberInterface
{
    protected string $fromState;

    public function __construct(protected MetricsHelper $metrics)
    {}

    public function stateLeave(LeaveEvent $event): void
    {
        $subject = $event->getSubject();
        $this->fromState = $subject instanceof DiaryKeeper ?
            $subject->getDiaryState() :
            null;
    }

    public function stateChanged(CompletedEvent $event): void
    {
        $subject = $event->getSubject();
        if (!$subject instanceof DiaryKeeper) {
            return;
        }

        $state = $subject->getDiaryState();
        if (!$state || $state === DiaryKeeper::STATE_NEW) {
            return;
        }

        $this->metrics->log(new DiaryStateEvent($subject, $this->fromState));
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.travel_diary_state.leave' => 'stateLeave',
            'workflow.travel_diary_state.completed' => 'stateChanged',
        ];
    }
}