<?php

namespace App\Controller\Cron;

use Exception;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;

class MessengerCronController extends AbstractCronController
{
    /**
     * @throws Exception
     */
    #[Route(path: '/messenger/consume', name: 'messengerconsumer')]
    public function messengerConsumer(KernelInterface $kernel): Response
    {
        return $this->runCommand(
            $kernel,
            'messenger:consume',
            [
                '--memory-limit' => '128M',
                '--time-limit' => 295, // die before the next scheduled run time (5 minutes less 5 seconds)
                'receivers' => ['async_notify', 'async_property_change', 'async_feedback', 'async_job'],
            ]
        );
    }
}
