<?php

namespace App\Command\Cron;

use App\Entity\Household;
use App\Repository\HouseholdRepository;
use App\Utility\DiaryKeeperFeaturesService;
use App\Utility\TravelDiary\RemindersHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'nts:cron:diary-keeper-week-start-reminders',
    description: 'Sends email reminder to Diary Keepers at start of diary week',
)]
class DiaryKeeperWeekStartRemindersCommand extends Command
{

    public function __construct(
        protected RemindersHelper $remindersHelper,
        protected HouseholdRepository $householdRepository,
        protected EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $householdsForReminding = $this->householdRepository->getHouseholdsForWeekStartReminder();
        $io->success(sprintf("Found %d households to remind", count($householdsForReminding)));

        $io->progressStart(count($householdsForReminding));
        foreach ($householdsForReminding as $household)
        {
            foreach ($household->getDiaryKeepers() as $diaryKeeper)
            {
                $this->remindersHelper->sendDiaryWeekReminderEmail($diaryKeeper);
            }
            $io->progressAdvance();
        }
        $io->progressFinish();
        $this->entityManager->flush();

        $io->success('Done');
        return Command::SUCCESS;
    }
}
