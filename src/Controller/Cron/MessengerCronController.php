<?php

namespace App\Controller\Cron;

use Exception;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;

class MessengerCronController extends AbstractCronController
{
    /**
     * @Route("/messenger/consume", name="messengerconsumer")
     * @throws Exception
     */
    public function messengerConsumer(KernelInterface $kernel): Response
    {
        return $this->runCommand(
            $kernel,
            'messenger:consume',
            [
                '--memory-limit' => '128M',
                '--time-limit' => 290, // die before the next scheduled run time (5 minutes less 10 seconds)
                'receivers' => ['async_notify', 'async_property_change'],
            ]
        );
    }
}
