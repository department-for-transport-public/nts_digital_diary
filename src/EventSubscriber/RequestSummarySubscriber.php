<?php

namespace App\EventSubscriber;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Emits a single, fully-populated log entry per request at kernel.response.
 *
 * The framework's own "Matched route" entry is logged during kernel.request, before the
 * firewall has run, so the UserProcessor/SessionProcessor metadata is empty on it. By the
 * time kernel.response fires the security token is populated and the request is still on the
 * RequestStack, so both processors enrich this record with the _ghost_meta values.
 */
class RequestSummarySubscriber implements EventSubscriberInterface
{
    public function __construct(
        protected readonly LoggerInterface $logger,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'onResponse',
        ];
    }

    public function onResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        $this->logger->info('Request handled', [
            'method' => $request->getMethod(),
            'path'   => $request->getPathInfo(),
            'route'  => $request->attributes->get('_route'),
            'status' => $event->getResponse()->getStatusCode(),
        ]);
    }
}
