<?php

namespace App\Controller\Cron;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TestController extends AbstractCronController
{
    /**
     * A test cron controller, to ensure that general cron route conditions are correct
     * @see "/config/routing/annotations.yaml"
     *
     * @Route("/test", name="test", condition="'test' === '%kernel.environment%'")
     * @return Response
     */
    public function test(): Response
    {
        return new Response("success");
    }
}
