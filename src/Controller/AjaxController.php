<?php

namespace App\Controller;

use App\Event\VideoJsEvent;
use App\Utility\Metrics\MetricsHelper;
use App\Utility\SessionTimeoutHelper;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route("/ajax", name: "app_remote_")]
class AjaxController extends AbstractController
{
    #[Route("/refresh-session", name: "refresh_session")]
    public function refreshSession(SessionTimeoutHelper $timeoutHelper): JsonResponse
    {
        return new JsonResponse([
            'warning' => $timeoutHelper->getWarningTime(),
            'expiry' => $timeoutHelper->getExpiryTime(),
        ]);
    }

    #[Route('/viewport-size', name: "viewport_size_monitor", methods: ["POST"])]
    public function reportViewportSize(Request $request): JsonResponse
    {
        $requestData = $request->request->all();
        if (array_keys($requestData) === ['size', 'aspect']) {
            $request->getSession()->set(MetricsHelper::VIEWPORT_DETAILS_SESSION_KEY, $requestData);
        }
        return new JsonResponse('"ok"');
    }

    #[Route('/video-event', name: "video_event", methods: ["POST"])]
    public function reportVideoEvent(EventDispatcherInterface $eventDispatcher, Request $request): JsonResponse
    {
        $requestData = $request->request->all();
        if (array_diff(['videoId', 'urlPath', 'type'], array_keys($requestData)) === []) {
            $eventDispatcher->dispatch(new VideoJsEvent(...$requestData));
        }
        return new JsonResponse('"ok"');
    }
}
