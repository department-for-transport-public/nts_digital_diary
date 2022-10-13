<?php

namespace App\Command\MaintenanceMode;

use App\Repository\MaintenanceLockRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class StatusCommand extends Command
{
    protected static $defaultName = 'nts:maintenance-mode:status';
    private SymfonyStyle $io;
    private MaintenanceLockRepository $maintenanceLockRepository;

    public function __construct(MaintenanceLockRepository $maintenanceLockRepository)
    {
        parent::__construct();
        $this->maintenanceLockRepository = $maintenanceLockRepository;
    }

    protected function configure()
    {
        $this
            ->setDescription('check the maintenance status')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        $this->status();

        return 0;
    }

    protected function status()
    {
        $whiteListedIPs = $this->maintenanceLockRepository->isLocked();
        if ($whiteListedIPs === false) {
            $this->io->success('Maintenance mode is NOT active.');
        } else {
            $this->io->success(sprintf('Maintenance mode is active. Whitelisted IPs: %s', implode(', ', $whiteListedIPs)));
        }
    }

    protected function validateIps(array $ips)
    {
        $result = array_diff($ips, filter_var_array($ips, FILTER_VALIDATE_IP));
        if (empty($result)) {
            return true;
        }
        $this->io->error(sprintf('You specified invalid whitelist IP(s): %s', implode(', ', $result)));
        return false;
    }
}
