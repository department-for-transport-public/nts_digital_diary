<?php

namespace App\Controller\Cron;

use Exception;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;

class PurgeOldEmailAddressesCronController extends AbstractCronController
{
    /**
     * @Route("/cleanup/purge-old-email-addresses", name="purge_old_email_addresses")
     * @throws Exception
     */
    public function messengerConsumer(KernelInterface $kernel): Response
    {
        return $this->runCommand(
            $kernel,
            'nts:cron:purge-old-email-addresses',
            []
        );
    }
}
