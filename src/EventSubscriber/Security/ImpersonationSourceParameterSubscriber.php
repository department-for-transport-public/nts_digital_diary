<?php

namespace App\EventSubscriber\Security;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ImpersonationSourceParameterSubscriber implements EventSubscriberInterface
{
    public function onRequest(RequestEvent $event)
    {
        if (!$event->getRequest()->query->get('_switch_user', false)) {
            return;
        }

        $event->getRequest()->getSession()->set('switch_user_source', $event->getRequest()->server->get('HTTP_REFERER'));
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onRequest', 12],
        ];
    }
}