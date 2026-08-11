<?php

namespace App\Command;

use App\Features;
use App\Utility\DirectOnboardingTestUrlGenerator;
use App\Utility\Screenshots\FixtureManager;
use App\Utility\Screenshots\SubprocessRequestHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;

#[AsCommand(name: 'nts:screenshots', description: 'Generate screenshots for the NTS service')]
class ScreenshotsCommand extends Command
{
    public function __construct(
        protected DirectOnboardingTestUrlGenerator $directOnboardingTestUrlGenerator,
        protected EntityManagerInterface           $entityManager,
        protected Features                         $features,
        protected FixtureManager                   $fixtureCreator,
        protected SubprocessRequestHelper          $subprocessRequestHelper,
        protected UserPasswordHasherInterface      $passwordHasher,
        protected string                           $frontendHostname,
        protected string                           $screenshotsPath,
        protected string                           $appEnvironment
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('extra-diary-stages', null, InputOption::VALUE_NONE, 'Add extra stages to the diary keeper screenshots')
            ->addOption('documentation', null, InputOption::VALUE_NONE, 'Generate (only) the screenshots for documentation')
            ->addOption('protocol', null, InputOption::VALUE_OPTIONAL, 'http or https', 'https');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($this->appEnvironment !== 'prod') {
            throw new \RuntimeException("The screenshots command must be run in the `prod` environment");
        }

        $this->fixtureCreator->deleteExistingInterviewer();
        $this->fixtureCreator->deleteExistingAccountSetupFixtures();
        $this->fixtureCreator->createInterviewer();
        $this->fixtureCreator->createAccountSetupFixtures();
        $this->fixtureCreator->createReflowFixtures();

        $path = dirname(__FILE__).'/../../screenshots/screenshots.js';

        if (!file_exists($path)) {
            throw new \RuntimeException("Screenshots JS command could not be found");
        }

        $outputBaseDir = realpath(dirname($path)).'/output';
        if (!is_dir($outputBaseDir)) {
            throw new \RuntimeException("Screenshots output directory missing (Expected at {$outputBaseDir})");
        }

        $outputDir = "{$outputBaseDir}/" . (new \DateTime())->format('Ymd-His');

        $protocol = $input->getOption('protocol');
        $processArgs = [
            $path,
            "frontend",
            "{$protocol}://{$this->frontendHostname}/",
            "{$outputDir}/",
        ];

        if ($input->getOption('extra-diary-stages')) {
            $processArgs[] = '--extra-diary-stages';
        }

        if ($input->getOption('documentation')) {
            $processArgs[] = '--documentation';
        }

        $input = new InputStream();

        $process = new Process($processArgs);
        $process->setTimeout(3600);
        $process->setInput($input);

        try {
            $process->disableOutput();
            $process->mustRun(function($type, $buffer) use ($input): void {
                $this->subprocessRequestHelper->process($buffer, $input);
            });
        } catch (ProcessFailedException $exception) {
            echo $exception->getMessage();
        }

        return Command::SUCCESS;
    }
}
