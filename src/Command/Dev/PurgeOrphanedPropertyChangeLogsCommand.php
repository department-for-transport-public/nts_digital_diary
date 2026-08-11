<?php

namespace App\Command\Dev;

use App\Repository\PropertyChangeLogRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'nts:dev:purge-orphaned-change-logs',
    description: 'Purges orphaned PropertyChangeLog entries whose Journey/Stage no longer exists',
)]
class PurgeOrphanedPropertyChangeLogsCommand extends Command
{
    public function __construct(protected PropertyChangeLogRepository $propertyChangeLogRepository)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show what would be deleted without actually deleting');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $isDryRun = $input->getOption('dry-run');

        $stats = $this->propertyChangeLogRepository->getOrphanedStats();
        $totalCount = array_sum(array_column($stats, 'count'));

        if ($totalCount === 0) {
            $io->success('No orphaned entries found');
            return Command::SUCCESS;
        }

        $rows = [];
        foreach ($stats as $stat) {
            $rows[] = [
                $stat['type'],
                $stat['count'],
                $stat['earliest']?->format('Y-m-d H:i:s') ?? '-',
                $stat['latest']?->format('Y-m-d H:i:s') ?? '-',
            ];
        }

        $io->table(['Type', 'Count', 'Earliest', 'Latest'], $rows);

        if ($isDryRun) {
            $io->warning(sprintf('Dry run — %d orphaned entries found but nothing was deleted', $totalCount));
            return Command::SUCCESS;
        }

        $deleted = $this->propertyChangeLogRepository->deleteOrphaned();
        $io->success(sprintf('Purged %d orphaned PropertyChangeLog entries', $deleted));

        return Command::SUCCESS;
    }
}
