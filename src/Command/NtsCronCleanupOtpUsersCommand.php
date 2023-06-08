<?php

namespace App\Command;

use App\Utility\Cleanup\OtpUserCleanupUtility;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'nts:cron:cleanup-otp-users',
    description: 'Removes old OTP users',
)]
class NtsCronCleanupOtpUsersCommand extends Command
{
    public function __construct(protected OtpUserCleanupUtility $otpUserCleanupUtility)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $count = $this->otpUserCleanupUtility->removeOldOtpUsers();

        $io->success(sprintf("Success - cleared %d old OTP user(s)", $count));

        return Command::SUCCESS;
    }
}
