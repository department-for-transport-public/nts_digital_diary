<?php

namespace App\Command\MaintenanceMode;

use App\Entity\Utility\MaintenanceLock;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'nts:maintenance-mode:unlock', description: 'Unlock the website for maintenance')]
class UnlockCommand extends Command
{
    public function __construct(protected readonly EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->unlock();
        $io->success('Maintenance mode is inactive.');

        return Command::SUCCESS;
    }

    protected function unlock(): void
    {
        $this->entityManager->createQueryBuilder()
            ->delete()
            ->from(MaintenanceLock::class, 'm')
            ->getQuery()
            ->execute();
    }
}
