<?php

namespace App\Controller\Cron;

use Exception;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;

class PurgeOldEmailAddressesCronController extends AbstractCronController
{
    /**
     * @throws Exception
     */
    #[Route(path: '/cleanup/purge-old-email-addresses', name: 'purge_old_email_addresses')]
    public function messengerConsumer(KernelInterface $kernel): Response
    {
        return $this->runCommand(
            $kernel,
            'nts:cron:purge-old-email-addresses',
            []
        );
    }
}
