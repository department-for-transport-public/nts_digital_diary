<?php

namespace App\EventSubscriber;

use App\Repository\MaintenanceLockRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

class MaintenanceLockSubscriber implements EventSubscriberInterface
{
    protected Environment $twig;
    protected RouterInterface $router;
    protected MaintenanceLockRepository $maintenanceLockRepository;

    protected const ROUTE_WHITELIST = [
        'app_home_index',
        'app_home_sitemap',
        'app_home_accessibilitystatement',
        'app_home_privacystatement',
    ];

    public function __construct(Environment $twig, RouterInterface $router, MaintenanceLockRepository $maintenanceLockRepository)
    {
        $this->twig = $twig;
        $this->router = $router;
        $this->maintenanceLockRepository = $maintenanceLockRepository;
    }

    public function onKernelRequest(RequestEvent $event)
    {
        if (!$this->isMaintenanceMode($event)) {
            return;
        }

        $event->setResponse(new Response(
            $this->twig->render('bundles/TwigBundle/Exception/maintenance.html.twig'),
            200, // 200, not 503, so that AppEngine doesn't take instances out of service
            ['X-Robots-Tag' => 'noindex']
        ));
    }

    protected function isMaintenanceMode(RequestEvent $event): bool
    {
        if ($this->isWhitelistedRoute($event)) {
            return false;
        }

        $whitelistIPs = $this->maintenanceLockRepository->isLocked();
        if ($whitelistIPs === false) {
            return false;
        }

        if (in_array($event->getRequest()->getClientIp(), $whitelistIPs)) {
            return false;
        }

        return true;
    }

    protected function isWhitelistedRoute(RequestEvent $event): bool
    {
        $routeName = $event->getRequest()->attributes->get('_route');

        if ($routeName === '_wdt') {
            return true;
        }

        if (1 === preg_match('/^_profiler/', $routeName)) {
            return true;
        }

        if (in_array($routeName, self::ROUTE_WHITELIST)) {
            return true;
        }

        return false;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'kernel.request' => ['onKernelRequest', 20],
        ];
    }
}