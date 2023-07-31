<?php

namespace App\Command\GcpDatabaseTools;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;

#[AsCommand(
    name: self::COMMAND_NAME_PREFIX . ':proxy',
    description: self::COMMAND_DESCRIPTION_PREFIX . ' - run proxy',
)]
class ProxyCommand extends AbstractGcpDatabaseCommand
{
    protected array $validEnvironmentsForCommand = ['dev', 'test', 'prod'];

    protected function runTask(): int
    {
        $this->startProxy(true);

        return Command::SUCCESS;
    }
}
