<?php


namespace App\Command\ExportApi;


use App\Security\HmacAuth\ClientKeyService;
use App\Security\HmacAuth\SecretGenerator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateKeyCommand extends Command
{
    protected static $defaultName = 'nts:export-api:create-client-key';
    protected static $defaultDescription = 'Create a client key for use with the export API';
    private ClientKeyService $clientKeyService;
    private SecretGenerator $secretGenerator;

    public function __construct(ClientKeyService $clientKeyService, SecretGenerator $secretGenerator)
    {
        parent::__construct();
        $this->clientKeyService = $clientKeyService;
        $this->secretGenerator = $secretGenerator;
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