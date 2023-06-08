<?php

namespace App\Command;

use App\Utility\Cleanup\SurveyPurgeUtility;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'nts:cron:purge-old-surveys',
    description: 'Purges surveys 200 days after they have been submitted',
)]
class NtsPurgeOldSurveysCommand extends Command
{
    public function __construct(protected SurveyPurgeUtility $surveyPurgeUtility)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $count = $this->surveyPurgeUtility->purgeOldSurveys();

        $io->success(sprintf("Success - purged %d old survey(s)", $count));

        return Command::SUCCESS;
    }
}
