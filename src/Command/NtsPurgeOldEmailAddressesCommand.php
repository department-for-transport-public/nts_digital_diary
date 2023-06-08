<?php

namespace App\Command;

use App\Utility\Cleanup\EmailAddressPurgeUtility;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'nts:cron:purge-old-email-addresses',
    description: 'Purges email addresses for users 60 days after a household has been submitted',
)]
class NtsPurgeOldEmailAddressesCommand extends Command
{
    public function __construct(protected EmailAddressPurgeUtility $emailAddressPurgeUtility)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $count = $this->emailAddressPurgeUtility->purgeOldEmailAddresses();

        $io->success(sprintf("Success - purged %d old email address(es)", $count));

        return Command::SUCCESS;
    }
}
