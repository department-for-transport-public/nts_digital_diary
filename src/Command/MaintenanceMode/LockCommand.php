<?php

namespace App\Command\MaintenanceMode;

use App\Entity\Utility\MaintenanceLock;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'nts:maintenance-mode:lock', description: 'Lock the website for maintenance')]
class LockCommand extends Command
{
    private SymfonyStyle $io;

    public function __construct(protected readonly EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'required - molly guard')
            ->addOption('whitelist-ip', 'w', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'comma separated list of IPs to whitelist')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);
        $whitelistIps = $input->getOption('whitelist-ip');
        if (!$input->getOption('force')) {
            $this->io->error('The --force option is required');
            return Command::FAILURE;
        }

        if (!$this->validateIps($whitelistIps)) {
            return Command::FAILURE;
        }

        $this->lock($whitelistIps);
        $this->io->success(sprintf('Maintenance mode is active. Whitelisted IPs: %s', implode(', ', $whitelistIps)));

        return Command::SUCCESS;
    }

    protected function lock(array $whitelistIps): void
    {
        try {
            $this->entityManager->beginTransaction();
            $this->entityManager->createQueryBuilder()
                ->delete()
                ->from(MaintenanceLock::class, 'm')
                ->getQuery()
                ->execute();

            $lock = (new MaintenanceLock())
                ->setWhitelistedIps($whitelistIps);

            $this->entityManager->persist($lock);
            $this->entityManager->flush();

            $this->entityManager->commit();
        }
        catch(\Exception) {
            $this->entityManager->rollback();
        }
    }

    protected function validateIps(array $ips): bool
    {
        $result = array_diff($ips, filter_var_array($ips, FILTER_VALIDATE_IP));
        if (empty($result)) {
            return true;
        }
        $this->io->error(sprintf('You specified invalid whitelist IP(s): %s', implode(', ', $result)));
        return false;
    }
}
