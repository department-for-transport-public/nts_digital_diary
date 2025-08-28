<?php

namespace App\Controller\Cron;

use Exception;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;

class CleanupOtpUsersCronController extends AbstractCronController
{
    /**
     * @throws Exception
     */
    #[Route(path: '/cleanup/otp-users', name: 'cleanup_otp_users')]
    public function messengerConsumer(KernelInterface $kernel): Response
    {
        return $this->runCommand(
            $kernel,
            'nts:cron:cleanup-otp-users',
            []
        );
    }
}
