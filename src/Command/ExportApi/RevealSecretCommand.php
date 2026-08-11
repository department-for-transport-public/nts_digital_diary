<?php

namespace App\Command\ExportApi;

use App\Repository\ApiUserRepository;
use App\Security\HmacAuth\ClientKeyService;
use App\Security\HmacAuth\SecretGenerator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'nts:export-api:reveal-secret', description: 'Show the secret for a given client key')]
class RevealSecretCommand extends Command
{
    public function __construct(
        protected readonly ApiUserRepository $apiUserRepository,
        protected readonly ClientKeyService  $clientKeyService,
        protected readonly SecretGenerator   $secretGenerator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('key', InputArgument::REQUIRED, 'the client key');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $apiUser = $this->apiUserRepository->findOneBy(['key' => $input->getArgument('key')]);
        $secret = base64_encode($this->secretGenerator->getSecretForApiUser($apiUser));
        $output->writeln("Secret: $secret");

        return Command::SUCCESS;
    }
}