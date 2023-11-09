<?php

namespace App\Command\GcpDatabaseTools;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Process\Process;

#[AsCommand(
    name: self::COMMAND_NAME_PREFIX . ':dump',
    description: self::COMMAND_DESCRIPTION_PREFIX . ' - dump database to file',
)]
class MysqlDumpCommand extends AbstractGcpDatabaseCommand
{
    protected array $validEnvironmentsForCommand = ['dev', 'test', 'prod'];

    protected function configure(): void
    {
        parent::configure();
        $this
            ->addOption('filename', 'f', InputOption::VALUE_OPTIONAL, 'the filename for the dump file');
    }

    protected function runTask(): int
    {
        $this->startProxy(false);

        return $this->mysqlDump();
    }

    protected function mysqlDump(): int
    {
        $dumpFilename = $this->input->getOption('filename') ?? "var/dumps/{$this->environment}-dump-" . date('Ymd-His') . ".sql";

        $dirName = dirname($dumpFilename);
        if (!file_exists($dirName)) {
            $this->io->error("output directory {$dirName} does not exist");
            return self::FAILURE;
        }

        if (file_exists($dumpFilename)) {
            $this->io->error("file {$dumpFilename} already exists");
            return self::FAILURE;
        }

        $this->io->writeln("Dumping <info>$this->environment</info> DB to <info>{$dumpFilename}</info>...");

        $process = new Process(['mysqldump', "--set-gtid-purged=OFF", "--single-transaction", "--no-tablespaces", "--socket={$this->getDbUnixSocket()}", "-u{$this->getDbUser()}", "-p", $this->getDbName()]);
        $process->setWorkingDirectory('/');
        $process->setTimeout(15*60);
        $process->start();

        foreach ($process as $type => $data) {
            if ($process::OUT === $type) {
                file_put_contents($dumpFilename, $data, FILE_APPEND);
            } else {
                if (!empty($data)) {
                    $this->io->warning($data);  // STDERR
                }
            }
        }
        if ($process->getExitCode() === self::SUCCESS) {
            $this->io->writeln("mysqldump written to <info>{$dumpFilename}</info>");
        }
        return $process->getExitCode();
    }
}
