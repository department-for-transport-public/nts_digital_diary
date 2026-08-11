<?php

namespace App\Messenger\Job\EventSubscriber;

use App\Messenger\Job\Job;
use App\Messenger\Job\JobHandler;
use JetBrains\PhpStorm\NoReturn;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Event\SendMessageToTransportsEvent;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;

class CreateJobStatusForNewMessageSubscriber implements EventSubscriberInterface
{
    public function __construct(protected JobHandler $jobHandler) {}

    public static function getSubscribedEvents(): array
    {
        return [
            SendMessageToTransportsEvent::class => 'sendMessageToTransports',
            WorkerMessageFailedEvent::class => 'onMessageFailed',
        ];
    }

    public function onMessageFailed(WorkerMessageFailedEvent $event): void
    {
        $job = $event->getEnvelope()->getMessage();
        if (!$job instanceof Job) {
            return;
        }
        $this->jobHandler->jobFailed($job, $event->getThrowable());
    }

    public function sendMessageToTransports(SendMessageToTransportsEvent $event): void
    {
        $job = $event->getEnvelope()->getMessage();
        if (!$job instanceof Job) {
            return;
        }
        $this->jobHandler->createJobStatus($job);
    }
}