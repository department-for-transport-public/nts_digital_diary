<?php


namespace App\Command\ExportApi;


use App\Repository\ApiUserRepository;
use App\Security\HmacAuth\ClientKeyService;
use App\Security\HmacAuth\SecretGenerator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateNonceCommand extends Command
{
    protected static $defaultName = 'nts:export-api:update-nonce';
    protected static $defaultDescription = 'Update the nonce for a given client key';
    private ClientKeyService $clientKeyService;
    private SecretGenerator $secretGenerator;
    private ApiUserRepository $apiUserRepository;

    public function __construct(ApiUserRepository $apiUserRepository, ClientKeyService $clientKeyService, SecretGenerator $secretGenerator)
    {
        parent::__construct();
        $this->clientKeyService = $clientKeyService;
        $this->secretGenerator = $secretGenerator;
        $this->apiUserRepository = $apiUserRepository;
    }

    protected function configure()
    {
        $this
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'required - molly guard')
            ->addOption('key', 'k', InputOption::VALUE_REQUIRED, 'the client key')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $apiUser = $this->apiUserRepository->findOneBy(['key' => $input->getOption('key')]);
        $this->clientKeyService->updateNonce($apiUser);
        $secret = base64_encode($this->secretGenerator->getSecretForApiUser($apiUser));
        $output->writeln("New secret: $secret");

        return Command::SUCCESS;
    }
}