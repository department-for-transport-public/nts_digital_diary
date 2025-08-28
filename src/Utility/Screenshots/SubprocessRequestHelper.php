<?php

namespace App\Utility\Screenshots;

use App\DataFixtures\Definition\JourneyDefinition;
use App\DataFixtures\Definition\OtherStageDefinition;
use App\DataFixtures\Definition\PrivateStageDefinition;
use App\DataFixtures\FixtureHelper;
use App\Entity\AreaPeriod;
use App\Entity\DiaryKeeper;
use App\Entity\Embeddable\Distance;
use App\Entity\Household;
use App\Entity\OtpUser;
use App\Entity\User;
use App\Features;
use App\Repository\Journey\MethodRepository;
use App\Security\OneTimePassword\PasscodeGenerator;
use App\Utility\DirectOnboardingTestUrlGenerator;
use App\Utility\TravelDiary\SerialHelper;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Workflow\WorkflowInterface;

class SubprocessRequestHelper
{
    public function __construct(
        protected DirectOnboardingTestUrlGenerator $directOnboardingTestUrlGenerator,
        protected EntityManagerInterface           $entityManager,
        protected MethodRepository                 $methodRepository,
        protected PasscodeGenerator                $passcodeGenerator,
        protected WorkflowInterface                $travelDiaryStateStateMachine,
    ) {}

    public function process(string $buffer, InputStream $inputStream): void
    {
        $lines = explode("\n", $buffer);
        foreach($lines as $line) {
            $line = trim($line);
            if (str_starts_with($line, 'SCREENSHOTS:')) {
                $command = substr($line, strlen('SCREENSHOTS:'));
                $this->processCommand($command, $inputStream);
            } else {
                echo "- " . $line . "\n";
            }
        }
    }

    public function processCommand(string $command, InputStream $inputStream): void
    {
        if (preg_match("/^addDocumentationFixtures\(\)$/", $command, $matches)) {
            $this->addDocumentationFixtures($matches, $inputStream);
        } else if (preg_match("/^approveAllDiaries\((?P<area>\d+),\s*(?P<addressNumber>\d+),\s*(?P<householdNumber>\d+)\)$/", $command, $matches)) {
            $this->approveAllDiaries($matches, $inputStream);
        } else if (preg_match("/^isFeatureEnabled\('(?P<feature>[^']*)'\)$/", $command, $matches)) {
            $this->isFeatureEnabled($matches, $inputStream);
        } else if (preg_match("/^getDirectOnboardingUrl\('(?P<username>[^']*)',\s*(?P<addressNumber>\d+),\s*(?P<diaryKeeperCount>\d+),\s*(?P<vehicleCount>\d+)\)$/", $command, $matches)) {
            $this->getDirectOnboardingUrl($matches, $inputStream);
        } else if (preg_match("/^getOnboardingCodes\((?P<area>\d+)\)$/", $command, $matches)) {
            $this->getOnboardingCodes($matches, $inputStream);
        } else if (preg_match("/^getOnboardingUrlFromEmail\('(?P<username>[^']*)'\)$/", $command, $matches)) {
            $this->getOnboardingUrlFromEmail($matches, $inputStream);
        } else if (preg_match("/^getCheckLetter\((?P<area>\d+),\s*(?P<addressNumber>\d+),\s*(?P<householdNumber>\d+)\)$/", $command, $matches)) {
            $this->getCheckLetter($matches, $inputStream);
        } else {
            dump("Unrecognised command: $command");
            $inputStream->write('FAIL: Unrecognised command');
        }
    }

    protected function addDocumentationFixtures(array $matches, InputStream $inputStream): void
    {
        $generateElliotJourneyDefs = function (int $day) {
            return [
                new JourneyDefinition($day, 'Home', '8:00am', 'Holt', '8:15am', 'To go to work', [
                    new PrivateStageDefinition(1, 'car', Distance::miles('3.1'), 15, 1, 0, true, null, "Elliot's Red Volvo")
                ]),
                new JourneyDefinition($day, 'Holt', '12:00pm', 'Holt', '12:40pm', 'To go for a walk', [
                    new OtherStageDefinition(1, 'walk', Distance::miles(2), 40, 1, 0)
                ]),
                new JourneyDefinition($day, 'Home', '5:00pm', 'Holt', '5:15pm', 'To go home', [
                    new PrivateStageDefinition(1, 'car', Distance::miles('3.1'), 15, 1, 0, true, null, "Elliot's Red Volvo")
                ]),
            ];
        };

        $generateAnikaJourneyDefs = function (int $day, bool $onlyFirstTwo) {
            $journeys = [
                new JourneyDefinition($day, 'Home', '8:15am', 'Hilperton', '8:35am', 'To take children to school', [
                    new PrivateStageDefinition(1, 'car', Distance::miles('2.5'), 20, 1, 2, true, null, "Anika's Red Fiat")
                ]),
                new JourneyDefinition($day, 'Hilperton', '8:40am', 'Home', '9:00am', 'To go home', [
                    new PrivateStageDefinition(1, 'car', Distance::miles('2.5'), 20, 1, 0, true, null, "Anika's Red Fiat")
                ]),
                new JourneyDefinition($day, 'Home', '2:45pm', 'Hilperton', '3:05pm', 'To pick children up from school', [
                    new PrivateStageDefinition(1, 'car', Distance::miles('2.5'), 20, 1, 0, true, null, "Anika's Red Fiat")
                ]),
                new JourneyDefinition($day, 'Hilperton', '3:10pm', 'Home', '3:30pm', 'To go home', [
                    new PrivateStageDefinition(1, 'car', Distance::miles('2.5'), 20, 1, 2, true, null, "Anika's Red Fiat")
                ]),
            ];

            return $onlyFirstTwo ? array_slice($journeys, 0, 2) : $journeys;
        };

        $journeys = [
            'elliot@example.com' => array_merge(
                $generateElliotJourneyDefs(1),
                $generateElliotJourneyDefs(2),
                $generateElliotJourneyDefs(3),
                $generateElliotJourneyDefs(4),
                $generateElliotJourneyDefs(5),
                $generateElliotJourneyDefs(6),
                $generateElliotJourneyDefs(7),
            ),
            'anika@example.com' => array_merge(
                $generateAnikaJourneyDefs(1, false),
                $generateAnikaJourneyDefs(2, false),
                $generateAnikaJourneyDefs(3, false),
                $generateAnikaJourneyDefs(4, false),
                $generateAnikaJourneyDefs(5, false),
                $generateAnikaJourneyDefs(6, true),
                $generateAnikaJourneyDefs(7, true),
            ),
        ];

        foreach($journeys as $email => $journeyDefinitions) {
            $user = $this->entityManager->getRepository(User::class)->loadUserByIdentifier($email);

            $diaryKeeper = $user->getDiaryKeeper();

            $vehicles = $diaryKeeper->getHousehold()->getVehicles()->toArray();

            foreach($journeyDefinitions as $definition) {
                $day = $diaryKeeper->getDiaryDayByNumber($definition->getDayNumber());
                $journey = FixtureHelper::createJourney($definition, $day);
                $day->addJourney($journey);

                $this->entityManager->persist($journey);

                foreach($definition->getStageDefinitions() as $stageDefinition) {
                    $stage = FixtureHelper::createStage($stageDefinition, $this->methodRepository, $vehicles);
                    $journey->addStage($stage);
                    $this->entityManager->persist($stage);
                }
            }
        }

        $this->entityManager->flush();

        $inputStream->write("OK: Created\n");
    }

    protected function isFeatureEnabled(array $matches, InputStream $inputStream): void
    {
        ['feature' => $featureName] = $matches;

        $answer = Features::isEnabled($featureName) ? 'true' : 'false';
        $inputStream->write("OK: {$answer}\n");
    }

    protected function getDirectOnboardingUrl(array $matches, InputStream $inputStream): void
    {
        ['username' => $username, 'addressNumber' => $addressNumber, 'diaryKeeperCount' => $diaryKeeperCount, 'vehicleCount' => $vehicleCount] = $matches;

        $url = $this->directOnboardingTestUrlGenerator->generateUrl($username, $addressNumber, $diaryKeeperCount, $vehicleCount, UrlGeneratorInterface::ABSOLUTE_URL);
        $inputStream->write("OK: {$url}\n");
    }

    protected function getOnboardingCodes(array $matches, InputStream $inputStream): void
    {
        ['area' => $area] = $matches;

        $area = $this->entityManager
            ->getRepository(AreaPeriod::class)
            ->findOneBy(['area' => $area]);

        if (!$area) {
            $inputStream->write("FAIL: Area not found");
            return;
        }

        $otpUsers = $this->entityManager
            ->getRepository(OtpUser::class)
            ->findForInterviewerDashboard($area);

        foreach($otpUsers as $otpUser) {
            if ($otpUser->getHousehold() !== null) {
                continue;
            }

            $id = $otpUser->getUserIdentifier();
            $code = $this->passcodeGenerator->getPasswordForUserIdentifier($id);

            $inputStream->write("OK: {$id},{$code}\n");
            return;
        }
    }

    protected function getOnboardingUrlFromEmail(array $matches, InputStream $inputStream): void
    {
        ['username' => $username] = $matches;

        $conn = $this->entityManager->getConnection();
        try {
            $result = $conn
                ->executeQuery(<<<SQL
    SELECT m.* 
    FROM messenger_messages m 
    WHERE m.body LIKE :username
    ORDER BY m.created_at DESC
    LIMIT 1
SQL, [
                    'username' => '%' . $username . '%',
                ])
                ->fetchAssociative();

            if (preg_match('#\\\\"url\\\\";s:\d+:\\\\"(?P<url>.+)\\\\";}#', $result['body'], $matches)) {
                $inputStream->write("OK: {$matches['url']}\n");
            } else {
                $inputStream->write("FAIL: Unable to parse url from message\n");
            }
        } catch (Exception $e) {
            $message = $e->getMessage();
            $inputStream->write("FAIL: {$message}\n");
        }
    }

    protected function getCheckLetter(array $matches, InputStream $inputStream): void
    {
        ['area' => $area, 'addressNumber' => $addressNumber, 'householdNumber' => $householdNumber] = $matches;

        $checkLetter = SerialHelper::getCheckLetter($area, $addressNumber, $householdNumber);
        $inputStream->write("OK: {$checkLetter}\n");
    }

    protected function approveAllDiaries(array $matches, InputStream $inputStream): void
    {
        ['area' => $area, 'addressNumber' => $addressNumber, 'householdNumber' => $householdNumber] = $matches;

        $household = $this->entityManager
            ->getRepository(Household::class)
            ->findOneBySerial($area, $addressNumber, $householdNumber, true);

        foreach($household->getDiaryKeepers() as $diaryKeeper) {
            if (in_array($diaryKeeper->getDiaryState(), [DiaryKeeper::STATE_NEW, DiaryKeeper::STATE_IN_PROGRESS])) {
                $diaryKeeper
                    ->setApprovalChecklistVerifiedBy('screenshots@example.com')
                    ->setApprovalChecklistVerifiedAt(new \DateTimeImmutable());

                $this->travelDiaryStateStateMachine->apply($diaryKeeper, DiaryKeeper::TRANSITION_COMPLETE);
            }

            if ($diaryKeeper->getDiaryState() === DiaryKeeper::STATE_COMPLETED) {
                $this->travelDiaryStateStateMachine->apply($diaryKeeper, DiaryKeeper::TRANSITION_APPROVE);
            }
        }

        $this->entityManager->flush();

        $inputStream->write("OK: Done\n");
    }
}
