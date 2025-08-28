<?php

namespace App\Controller\Cron;

use Exception;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;

class PurgeOldSurveysCronController extends AbstractCronController
{
    /**
     * @throws Exception
     */
    #[Route(path: '/cleanup/purge-old-surveys', name: 'purge_old_surveys')]
    public function messengerConsumer(KernelInterface $kernel): Response
    {
        return $this->runCommand(
            $kernel,
            'nts:cron:purge-old-surveys',
            []
        );
    }
}
