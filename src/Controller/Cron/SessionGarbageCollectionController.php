<?php

namespace App\Controller\Cron;

use Exception;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;

class SessionGarbageCollectionController extends AbstractCronController
{
    /**
     * @throws Exception
     */
    #[Route(path: '/session-garbage-collection', name: 'session_garbage_collection')]
    public function messengerConsumer(KernelInterface $kernel): Response
    {
        return $this->runCommand(
            $kernel,
            'nts:cron:session-garbage-collection',
            []
        );
    }
}
