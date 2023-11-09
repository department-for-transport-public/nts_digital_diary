<?php

namespace App\EventSubscriber;

use App\Attribute\RequiresGoogleCloudStorageStreamWrapper;
use Google\Cloud\Storage\StorageClient;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class RequiresGoogleCloudStorageStreamWrapperSubscriber implements EventSubscriberInterface
{
    /**
     * @throws \ReflectionException
     */
    public function onKernelController(KernelEvent $event): void
    {
        $controller = $event->getController();
        $r = match(true) {
            \is_array($controller) => new \ReflectionMethod($controller[0], $controller[1]),
            \is_object($controller) && \is_callable([$controller, '__invoke'])
                => new \ReflectionMethod($controller, '__invoke'),
            default => new \ReflectionFunction($controller),
        };
        if ($r->getAttributes(RequiresGoogleCloudStorageStreamWrapper::class)) {
            (new StorageClient())->registerStreamWrapper();
        }
    }
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => [
                ['onKernelController', 0],
            ],
        ];
    }
}