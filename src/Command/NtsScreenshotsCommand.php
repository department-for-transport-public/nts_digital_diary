<?php

namespace App\Command;

use App\Entity\User;
use App\Utility\Screenshots\AbstractScreenshotter;
use App\Utility\Screenshots\FixtureManager;
use App\Utility\Screenshots\InterviewerScreenshotter;
use App\Utility\Screenshots\DiaryKeeperScreenshotter;
use App\Utility\Screenshots\OnboardingScreenshotter;
use App\Utility\Screenshots\ScreenshotsException;
use Doctrine\ORM\EntityManagerInterface;
use Nesk\Puphpeteer\Puppeteer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class NtsScreenshotsCommand extends Command
{
    protected static $defaultName = 'nts:screenshots';
    protected static $defaultDescription = 'Generate screenshots for the NTS service';

    protected EntityManagerInterface $entityManager;
    protected FixtureManager $fixtureCreator;
    protected UserPasswordHasherInterface $passwordHasher;
    protected string $screenshotsPath;
    protected string $frontendHostname;
    private string $appEnvironment;

    public function __construct(EntityManagerInterface $entityManager, FixtureManager $fixtureCreator, UserPasswordHasherInterface $passwordHasher, string $frontendHostname, string $screenshotsPath, string $appEnvironment)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->fixtureCreator = $fixtureCreator;
        $this->passwordHasher = $passwordHasher;

        $this->frontendHostname = $frontendHostname;
        $this->screenshotsPath = $screenshotsPath;
        $this->appEnvironment = $appEnvironment;
    }

    protected function configure(): void
    {
        $this
            ->addOption('extra-diary-stages', null, InputOption::VALUE_NONE, 'Add extra stages to the diary keeper screenshots');
    }

    /**
     * @throws ScreenshotsException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($this->appEnvironment !== 'prod') {
            throw new \RuntimeException("The screenshots command must be run in the `prod` environment");
        }

        $this->fixtureCreator->deleteExistingInterviewer();
        $this->fixtureCreator->createInterviewer();

        $outputDir = "{$this->screenshotsPath}/screenshots-".(new \DateTime())->format('Ymd-His');
        $hostname = "https://{$this->frontendHostname}";
        $diaryUserPassword = 'password';

        $puppeteer = new Puppeteer([
            'debug' => true,
        ]);

        $browser = $puppeteer->launch([
            'ignoreHTTPSErrors' => true,
            'args' => [
                '--single-process',
            ],
        ]);

        try {
            $interviewerScreenshotter = new InterviewerScreenshotter($browser, "{$outputDir}/interviewer/", $hostname);
            $onboardingScreenshotter = new OnboardingScreenshotter($browser, "{$outputDir}/onboarding/", $hostname);
            $diaryKeeperScreenshotter = new DiaryKeeperScreenshotter($browser, "{$outputDir}/diary-keeper/", $hostname, $input->getOption('extra-diary-stages'));

            [$passcode1, $passcode2] = $interviewerScreenshotter->retrieveOnboardingCodes();

            $userIdentifier = $onboardingScreenshotter->onboardingFlow($passcode1, $passcode2);
            $this->setUserPassword($userIdentifier, $diaryUserPassword);

            $diaryKeeperScreenshotter->diaryFlow($userIdentifier, $diaryUserPassword);
            $interviewerScreenshotter->loginAndOnboardingCodesFlow();
        }
        catch(ScreenshotsException $e) {
            $io->error($e->getMessage());

            $page = $e->getPage();
            if ($page) {
                try {
                    AbstractScreenshotter::takeScreenshot($page, "{$outputDir}/error.png");
                }
                catch(\Exception $e) {
                    $io->error('Failed to write error screenshot');
                    $io->error($e->getMessage());
                }
            }

            throw $e;
        }

        return Command::SUCCESS;
    }

    /**
     * @throws ScreenshotsException
     */
    public function setUserPassword(string $userIdentifier, string $password): void
    {
        $user = $this->entityManager->getRepository(User::class)
            ->loadUserByIdentifier($userIdentifier);

        if (!$user) {
            throw new ScreenshotsException('Unable to retrieve diary user');
        }

        $user->setPlainPassword($password);
        $this->entityManager->flush();
    }
}
