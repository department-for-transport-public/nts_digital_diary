<?php

namespace App\Command\Dev;


use App\Entity\AreaPeriod;
use App\Entity\Household;
use App\Event\CompleteOnboardingEvent;
use App\EventSubscriber\Onboarding\OnboardingType;
use App\Repository\AreaPeriodRepository;
use App\Repository\HouseholdRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand(name: 'nts:dev:add-household', description: 'Add a household')]
class AddHouseholdCommand extends Command
{
    protected SymfonyStyle $io;

    public function __construct(
        protected HouseholdRepository $householdRepository,
        protected AreaPeriodRepository $areaPeriodRepository,
        protected EntityManagerInterface $entityManager,
        protected EventDispatcherInterface $eventDispatcher,
        protected ValidatorInterface $validator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('serialNumber', InputArgument::REQUIRED, 'full serial number')
            ->addOption('startDate', 's', InputOption::VALUE_OPTIONAL, 'Diary week start date (Y-m-d)')
        ;
    }

    /**
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

//        if (!preg_match(
//            '/^(?<area>' . AreaPeriod::AREA_PATTERN . ')\/(?<address>\d{1,2})\/(?<household>\d)$/',
//            $input->getArgument('serialNumber'),
//            $matches)
//        ) {
//            $io->error('Invalid serial');
//            return 1;
//        }
        [$area, $address, $householdNo] = explode('/', (string) $input->getArgument('serialNumber'));

        $household = $this->householdRepository->findOneBySerial($area, $address, $householdNo, true);
        if ($household) {
            $this->io->error('Household already exists');
            return Command::FAILURE;
        }

        $startDate = $input->getOption('startDate');
        if ($startDate && !preg_match('/^2\d{3}-\d{2}-\d{2}$/', (string) $startDate)) {
            $this->io->error('Invalid start date');
            return Command::FAILURE;
        }

        $household = $this->createHousehold(
            $area,
            $address,
            $householdNo,
            $startDate ? new DateTime($startDate) : null
        );
        if ($household) {
            $this->eventDispatcher->dispatch(new CompleteOnboardingEvent($household, OnboardingType::CLI_ADD_HOUSEHOLD));
            return Command::SUCCESS;
        }

        return Command::FAILURE;
    }

    protected function createHousehold(string $area, string $address, string $household, ?DateTime $diaryWeekStartDate = null): ?Household
    {
        $areaPeriod = $this->areaPeriodRepository->findOneBy(['area' => $area]);
        if (!$areaPeriod) {
            $areaPeriod = (new AreaPeriod())
                ->setArea($area)
                ->populateMonthAndYearFromArea();

            $violations = $this->validator->validate($areaPeriod, null, "cli.add-area-period");
            if ($violations->count() > 0) {
                $this->io->error(array_map(fn(ConstraintViolation $v): string|\Stringable => $v->getMessage(), iterator_to_array($violations)));
                return null;
            }

            $this->entityManager->persist($areaPeriod);
        }

        $household = (new Household())
            ->setAreaPeriod($areaPeriod)
            ->setAddressNumber($address)
            ->setHouseholdNumber($household)
            ->setDiaryWeekStartDate($diaryWeekStartDate ?? new DateTime('now'))
        ;
        $violations = $this->validator->validate($household, null, "cli.add-household");
        if ($violations->count() > 0) {
            $this->io->error(array_map(fn(ConstraintViolation $v): string|\Stringable => $v->getMessage(), iterator_to_array($violations)));
            return null;
        }

        $this->entityManager->persist($household);
        return $household;
    }
}
