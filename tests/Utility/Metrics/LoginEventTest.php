<?php

namespace App\Tests\Utility\Metrics;

use App\Entity\Interviewer;
use App\Entity\OtpUser;
use App\Entity\User;
use App\Entity\Utility\MetricsLog;
use App\Repository\Utility\MetricsLogRepository;
use App\Security\OneTimePassword\PasscodeGenerator;
use App\Security\OneTimePassword\TrainingUserProvider;
use App\Tests\DataFixtures\OtpUserFixtures;
use App\Tests\DataFixtures\UserFixtures;
use App\Tests\Functional\AbstractFunctionalTestCase;
use App\Utility\Metrics\MetricsHelper;
use App\Utility\Security\UrlSigner;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Router;

class LoginEventTest extends AbstractFunctionalTestCase
{
    protected EntityManagerInterface $entityManager;
    protected MetricsLogRepository $metricsRepository;
    protected PasscodeGenerator $passcodeGenerator;
    protected UrlSigner $urlSigner;
    protected Router $router;

    protected function setUp(): void
    {
        $this->initialiseClientAndLoadFixtures([UserFixtures::class, OtpUserFixtures::class]);
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->metricsRepository = self::getContainer()->get(MetricsLogRepository::class);
        $this->passcodeGenerator = self::getContainer()->get(PasscodeGenerator::class);
        $this->urlSigner = self::getContainer()->get(UrlSigner::class);
        $this->router = self::getContainer()->get('router');

        // Remove all logs left by fixtures
        $this->entityManager->createQueryBuilder()->delete(MetricsLog::class, 'm')->getQuery()->execute();
    }

    public function dataMainFirewallLoginEvent(): array
    {
        return [
            ['diary-keeper-adult@example.com', 'password', true], // DK exists success
            ['diary-keeper-adult@example.com', 'invalid', false], // DK exists fail
            ['interviewer@example.com', 'password', true], // int exists success
            ['interviewer@example.com', 'invalid', false], // int exists fail
            ['nobody@example.com', 'invalid', false], // doesn't exist
            ['', '', false], // doesn't exist
        ];
    }

    /**
     * @dataProvider dataMainFirewallLoginEvent
     */
    public function testMainFirewallLoginEvent(?string $userIdentifier, ?string $password, bool $expectSuccess): void
    {
        $this->loginUser($userIdentifier, $password);
        $this->assertMainLoginSuccess($expectSuccess);
        $metrics = $this->metricsRepository->findAll();
        self::assertCount($expectSuccess ? 1 : 0, $metrics);
        if ($expectSuccess) {
            /** @var MetricsLog $metric */
            $metric = $metrics[0];
            $user = $this->entityManager->getRepository(User::class)->loadUserByIdentifier($userIdentifier);
            $expectedUserSerial = $user?->getInterviewer()
                ? 'int:' . $user?->getInterviewer()?->getSerialId()
                : 'dk:' . $user?->getDiaryKeeper()?->getSerialNumber(...MetricsHelper::GET_SERIAL_METHOD_ARGS);
            self::assertEquals('main', $metric->getMetadata()['firewall']);
            self::assertEquals(null, $metric->getDiarySerial());
            self::assertEquals('Login: success', $metric->getEvent());
            self::assertEquals($expectedUserSerial, $metric->getUserSerial());
        }
    }

    public function assertMainLoginSuccess(bool $isSuccessful): void
    {
        $selector = '#user_login_group-error';
        if ($isSuccessful) {
            self::assertSelectorTextNotContains('h1', 'Sign in');
            self::assertEmpty($this->client->getCrawler()->filter($selector));
        } else {
            self::assertSelectorTextContains('h1', 'Sign in');
            self::assertNotEmpty($this->client->getCrawler()->filter($selector));
        }
    }

    public function dataOnboardingFirewallLoginEvent(): array
    {
        return [
            ['1234567890', true],
            ['1234567890', false],
            ['invalid', false],
        ];
    }

    public function assertOnboardingLoginSuccess(bool $isSuccessful): void
    {
        $selector = '#otp_login_group-error';
        if ($isSuccessful) {
            self::assertSelectorTextContains('h1', 'Setting up the household');
            self::assertEmpty($this->client->getCrawler()->filter($selector));
        } else {
            self::assertSelectorTextContains('h1', 'Sign in with a one-time password');
            self::assertNotEmpty($this->client->getCrawler()->filter($selector));
        }
    }

    /**
     * @dataProvider dataOnboardingFirewallLoginEvent
     */
    public function testOnboardingFirewallLoginEvent(?string $passcode1, bool $expectSuccess): void
    {
        $this->loginOtpUser($passcode1, $expectSuccess ? $this->passcodeGenerator->getPasswordForUserIdentifier($passcode1) : '123');
        $this->assertOnboardingLoginSuccess($expectSuccess);
        $metrics = $this->metricsRepository->findAll();

        self::assertCount($expectSuccess ? 1 : 0, $metrics);
        if ($expectSuccess) {
            $otpUser = $this->entityManager->getRepository(OtpUser::class)->loadUserByIdentifier($passcode1);
            $this->validateOnboardingLoginMetrics($metrics[0], $passcode1, $expectSuccess, $otpUser?->getAreaPeriod()?->getArea());
        }
    }

    protected function validateOnboardingLoginMetrics(MetricsLog $metric, ?string $passcode1, bool $expectSuccess, ?string $expectedArea = null): void
    {
        /** @var OtpUser $otpUser */
        self::assertEquals('ob:' . $passcode1, $metric->getUserSerial());
        self::assertEquals($expectSuccess ? 'Login: success' : 'Login: failure', $metric->getEvent());
        self::assertEquals($expectedArea, $metric->getDiarySerial());
    }

    public function dataOnboardingTrainingLoginEvent(): array
    {
        return [
            [TrainingUserProvider::USER_IDENTIFIER, true],
            [TrainingUserProvider::USER_IDENTIFIER, false],
            ['1234567890', false], // real OTP user, but not valid for onboarding training
            ['invalid', false],
        ];
    }

    /**
     * @dataProvider dataOnboardingTrainingLoginEvent
     */
    public function testOnboardingTrainingLoginEvent(?string $passcode1, bool $expectSuccess): void
    {
        /** @var Interviewer $interviewer */
        $interviewer = $this->getFixtureByReference('interviewer');
        $this->loginUser($interviewer->getUser()->getUserIdentifier());
        $this->client->request('GET', '/interviewer/training');

        // navigate to onboarding training login
        $this->client->request('GET', '/interviewer/training');
        $this->client->request('GET', '/interviewer/training/module/onboarding-practice');
        $this->clickLinkContaining('Access onboarding training');

        $this->client->submitForm('otp_login_sign_in', [
            'otp_login[group][identifier]' => $passcode1,
            'otp_login[group][passcode]' => $expectSuccess ? $this->passcodeGenerator->getPasswordForUserIdentifier($passcode1) : 'invalid',
        ]);
        $this->assertOnboardingLoginSuccess($expectSuccess);

        $metrics = $this->metricsRepository->findAll();
        self::assertCount(1, $metrics);
        // First metric is expected to be the Interviewer login event
        // ensure only 1, so not logging onboarding training login
    }
}