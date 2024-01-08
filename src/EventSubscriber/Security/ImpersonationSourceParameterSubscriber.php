<?php

namespace App\EventSubscriber\Security;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Http\Event\SwitchUserEvent;

class ImpersonationSourceParameterSubscriber implements EventSubscriberInterface
{
    public function onSwitchUser(SwitchUserEvent $event): void
    {
        $source = $event->getRequest()->server->get('HTTP_REFERER');
        $event->getRequest()->getSession()->set('switch_user_source', $source);
        $event->getRequest()->attributes->set('switch_user_start', $source);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SwitchUserEvent::class => 'onSwitchUser',
        ];
    }
}