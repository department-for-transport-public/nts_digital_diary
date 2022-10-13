<?php

namespace App\Controller\Cron;

use Exception;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;

class PropertyChangeCronController extends AbstractCronController
{
    /**
     * @Route("/messenger/property-change-consume", name="messenger_property_change_consumer")
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
                'receivers' => ['async_property_change'],
            ]
        );
    }
}
