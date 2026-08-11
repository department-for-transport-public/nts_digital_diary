<?php

namespace App\Command\Cron;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'nts:cron:session-garbage-collection',
    description: 'GC for PDOSessionHandler',
)]
class NtsSessionGarbageCollectionCommand extends Command
{
    public function __construct(protected Connection $connection)
    {
        parent::__construct();
    }

    /**
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $count = $this->connection->executeStatement(
            'DELETE FROM sessions WHERE sess_lifetime < UNIX_TIMESTAMP()'
        );

        $output->writeln("Deleted $count expired sessions.");

        return Command::SUCCESS;
    }
}
