<?php

namespace App\Command\DataProtection;

use App\Repository\HouseholdRepository;
use App\Repository\OtpUserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'nts:data-protection:delete-household', description: 'Delete a household')]
class DeleteHouseholdCommand extends Command
{
    protected SymfonyStyle $io;

    public function __construct(
        protected HouseholdRepository      $householdRepository,
        protected OtpUserRepository        $otpUserRepository,
        protected EntityManagerInterface   $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('serialNumber', InputArgument::REQUIRED, 'full serial number')
        ;
    }

    /**
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        $serialNumber = (string) $input->getArgument('serialNumber');
        if (!preg_match('#^\d{6}/\d{1,2}/\d{1,2}$#', $serialNumber)) {
            $this->io->error("Serial number should be in the format 123456/01/1");
            return Command::FAILURE;
        }

        [$area, $address, $householdNo] = explode('/', $serialNumber);

        $household = $this->householdRepository->findOneBySerial($area, $address, $householdNo, false);
        if (!$household) {
            $this->io->error('Household does not exist');
            return Command::FAILURE;
        }
        $otpUser = $this->otpUserRepository->findOneBy(['household' => $household]);

        if ($otpUser) {
            $this->entityManager->remove($otpUser);
        }

        $this->entityManager->remove($household);
        $this->entityManager->flush();

        return Command::SUCCESS;
    }
}
