<?php

namespace App\Controller\Cron;

use Exception;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;

class SendDiaryKeeperWeekStartRemindersCronController extends AbstractCronController
{
    /**
     * @throws Exception
     */
    #[Route(path: '/send-diary-keeper-week-start-reminders', name: 'send_diary_keeper_week_start_reminders')]
    public function sendReminders(KernelInterface $kernel): Response
    {
        return $this->runCommand(
            $kernel,
            'nts:cron:send-diary-keeper-week-start-reminders',
            []
        );
    }
}
