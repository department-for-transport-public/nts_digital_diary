<?php

namespace App\Controller;

use App\Utility\SessionTimeoutHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class CommonController extends AbstractController
{
    /**
     * @Route("/refresh-session", name="app_refresh_session")
     */
    public function refreshSession(SessionTimeoutHelper $timeoutHelper): JsonResponse
    {
        return new JsonResponse([
            'warning' => $timeoutHelper->getWarningTime(),
            'expiry' => $timeoutHelper->getExpiryTime(),
        ]);
    }
}
