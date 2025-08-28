<?php

namespace App\Tests\Functional\OnBoarding;

use App\Entity\Household;
use App\Security\OneTimePassword\PasscodeGenerator;
use App\Tests\DataFixtures\TestSpecific\IntroductionBackCrashFixtures;
use App\Tests\Functional\AbstractProceduralWizardTest;
use App\Tests\Functional\Wizard\Action\Context;
use App\Tests\Functional\Wizard\Form\FormTestCase;
use App\Utility\TravelDiary\SerialHelper;
use Symfony\Component\DomCrawler\Crawler;

class IntroductionBackCrashTest extends AbstractProceduralWizardTest
{
    public const USER_NOT_ONBOARDED = '1234567890';

    protected PasscodeGenerator $passcodeGenerator;

    public function setUp(): void
    {
        $this->initialiseClientAndLoadFixtures([
            IntroductionBackCrashFixtures::class
        ]);

        $this->context = $this->createContext('');
        $this->passcodeGenerator = self::getContainer()->get(PasscodeGenerator::class);
    }

    public function tearDown(): void
    {
        try {
            $this->client->clickLink('Logout');
        } catch(\Throwable) {}
        parent::tearDown();
    }

    public function testOnboardingIntroductionBackCrash(): void
    {
        $this->markTestSkipped('Chrome v128 breaks this test; seems to be related to its changes to the --headless option (see comments in code)');
        // See comments in DiaryKeeperBackCrashTest.php

        $identifier = self::USER_NOT_ONBOARDED; // OTP user where nothing has been previously entered
        $this->loginOtpUser($identifier, $this->passcodeGenerator->getPasswordForUserIdentifier($identifier));

        $this->callbackTestAction($this->expectedNumberOfHouseholdsInDatabase(0));

        $this->formTestAction(
            '/onboarding/household/introduction',
            'form_button_group_continue',
            [new FormTestCase([])]
        );

        $this->formTestAction(
            '/onboarding/household/details',
            'household_button_group_continue',
            [
                new FormTestCase([
                    'household[addressNumber]' => '8',
                    'household[householdNumber]' => '1',
                    'household[checkLetter]' => SerialHelper::getCheckLetter(111984, 8, 1),
                    "household[diaryWeekStartDate][day]" => "20",
                    "household[diaryWeekStartDate][month]" => "01",
                    "household[diaryWeekStartDate][year]" => "2022",
                ])
            ]
        );

        $this->callbackTestAction($this->expectedNumberOfHouseholdsInDatabase(1));

        $this->callbackTestAction(function(Context $context): void {
            $client = $context->getClient();

            $client->back();
            $client->back();

            $crawler = new Crawler($client->getPageSource());
            $title = $crawler->filter('title')->text();

            $testCase = $context->getTestCase();

            $testCase->assertStringContainsString('Setting up the household - National Travel Survey - GOV.UK', $title);
        });

        // This back, back, submit nonsense previously caused an error in PropertyMerger
        $this->formTestAction(
            '/onboarding/household/introduction',
            'form_button_group_continue',
            [new FormTestCase([])]
        );

        $this->formTestAction(
            '/onboarding/household/details',
            'household_button_group_continue',
            [
                new FormTestCase([
                    'household[addressNumber]' => '8',
                    'household[householdNumber]' => '3',
                    'household[checkLetter]' => SerialHelper::getCheckLetter(111984, 8, 3),
                    "household[diaryWeekStartDate][day]" => "20",
                    "household[diaryWeekStartDate][month]" => "01",
                    "household[diaryWeekStartDate][year]" => "2022",
                ])
            ]
        );

        // Just check that this hasn't somehow created a second household
        $this->callbackTestAction($this->expectedNumberOfHouseholdsInDatabase(1));
    }

    protected function expectedNumberOfHouseholdsInDatabase(int $expectedCount): \Closure
    {
        return function (Context $context) use ($expectedCount): void {
            $entityManager = $context->getEntityManager();
            $households = $entityManager->createQueryBuilder()
                ->select('h')
                ->from(Household::class, 'h')
                ->getQuery()
                ->execute();

            $actualCount = count($households);
            $context->getTestCase()->assertEquals($expectedCount, $actualCount, "Incorrect number of households in database");
        };
    }
}