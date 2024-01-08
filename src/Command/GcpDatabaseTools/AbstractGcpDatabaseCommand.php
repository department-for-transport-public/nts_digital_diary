<?php

namespace App\Command\GcpDatabaseTools;

use Doctrine\DBAL\Tools\DsnParser;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * Requires json GCP_DATABASE_COMMAND_CONFIG env var defining (in .env.local)
 * Should be associative array of {gcp environment names => database url}
 * e.g.
 * GCP_DATABASE_COMMAND_CONFIG='{"dev":"mysql://dbuser@localhost/nts-dev?unix_socket=/cloudsql/gcp-project-name:region:instance-name"}'
 */
abstract class AbstractGcpDatabaseCommand extends Command
{
    protected array $validEnvironmentsForCommand = [];

    protected string $environment;
    protected SymfonyStyle $io;
    protected InputInterface $input;
    protected Process $proxy;
    const COMMAND_NAME_PREFIX = 'nts:gcp-database';
    const COMMAND_DESCRIPTION_PREFIX = 'GCP database tools';

    public function __construct(protected readonly array $config)
    {
        parent::__construct(null);
    }

    protected function configure(): void
    {
        $this
            ->addArgument('environment', InputArgument::REQUIRED, 'The AppEngine environment');
    }

    abstract protected function runTask(): int;

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $this->io = new SymfonyStyle($input, $output);
        $this->environment = $input->getArgument('environment');

        if (!array_key_exists($this->environment, $this->config)) {
            $this->io->error("Environment not defined: $this->environment");
            return Command::FAILURE;
        }

        if (!in_array($this->environment, $this->validEnvironmentsForCommand)) {
            $this->io->error("Environment '$this->environment' not valid for command");
            return self::FAILURE;
        }

        $this->setProject();

        $result = $this->runTask();
        $this->stopProxy();
        return $result;
    }

    protected function setProject(): void
    {
        $process = new Process(['gcloud', "config", "set", "project", $this->getDbProject()]);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $this->io->writeln("Project set to <info>{$this->getDbProject()}</info>");
    }

    protected function startProxy(bool $wait = true): void
    {
        $this->io->write("Starting proxy in <info>{$this->getDbSocketDir()}</info>... ");

        $process = new Process(['cloud-sql-proxy', "--unix-socket={$this->getDbSocketDir()}", $this->getDbInstanceId()]);
        $process->setTimeout(15*60);
        $process->start();

        foreach ($process as $type => $data) {
            if ($process::ERR === $type) {
                $this->io->warning($data);  // STDERR
            }
            if (stripos($data, 'Ready for new connections') !== false) {
                $this->proxy = $process;
                $this->io->writeln('ready for connections');
                if ($wait) {
                    $this->io->write("Press enter to terminate proxy");
                    $handle = fopen ("php://stdin","r");
                    fgets($handle);
                    $this->stopProxy();
                }
                return;
            }
        }
    }

    private function stopProxy(): void
    {
        if (isset($this->proxy)) {
            $this->io->writeln('Terminating proxy');
            $this->proxy->stop();
            unset($this->proxy);
        }
    }

    protected function getDbUser(): string
    {
        return $this->getDbParams()['user'];
    }

    protected function getDbName(): string
    {
        return $this->getDbParams()['dbname'];
    }

    protected function getDbHost(): string
    {
        return $this->getDbParams()['host'];
    }

    protected function getDbUnixSocket(): string
    {
        return $this->getDbParams()['unix_socket'];
    }

    protected function getDbInstanceId(): string
    {
        $parts = explode('/', $this->getDbParams()['unix_socket']);
        return array_pop($parts);
    }

    protected function getDbProject(): string
    {
        $regex = '@(?<project>[^:]+)@';
        preg_match($regex, $this->getDbInstanceId(), $matches);
        return $matches['project'];
    }

    protected function getDbSocketDir(): string
    {
        $parts = explode('/', $this->getDbParams()['unix_socket']);
        array_pop($parts);
        return implode('/', $parts);
    }

    private function getDbParams(): array
    {
        $parser = new DsnParser();
        return $parser->parse($this->config[$this->environment]);
    }
}
