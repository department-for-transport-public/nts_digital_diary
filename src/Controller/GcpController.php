<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class GcpController extends AbstractController
{
    /**
     * @Route("/start", name="app_gcp_warmup")
     */
    public function warmup(): Response
    {
        return new Response("success");
    }

    /**
     * @Route("/stop", name="app_gcp_cooldown")
     */
    public function shutdown(): Response
    {
        return new Response("success");
    }
}
