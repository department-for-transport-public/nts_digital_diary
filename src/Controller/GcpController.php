<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class GcpController extends AbstractController
{
    #[Route("/warmup", name:"app_gcp_warmup")]
    public function warmup(): Response
    {
        return new Response("success");
    }
}
