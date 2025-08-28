<?php


namespace App\Command\ExportApi;


use App\Security\HmacAuth\ClientKeyService;
use App\Security\HmacAuth\SecretGenerator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'nts:export-api:create-client-key', description: 'Create a client key for use with the export API')]
class CreateKeyCommand extends Command
{
    public function __construct(
        protected readonly ClientKeyService $clientKeyService,
        protected readonly SecretGenerator  $secretGenerator,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $apiUser = $this->clientKeyService->createClientKey();
        $output->writeln("Key:    {$apiUser->getKey()}");
        $secret = base64_encode($this->secretGenerator->getSecretForApiUser($apiUser));
        $output->writeln("Secret: $secret");

        return Command::SUCCESS;
    }
}