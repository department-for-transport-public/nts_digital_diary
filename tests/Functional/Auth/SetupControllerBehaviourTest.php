<?php

namespace App\Tests\Functional\Auth;

use App\Entity\DiaryKeeper;
use App\Entity\Interviewer;
use App\Messenger\AlphagovNotify\Email;
use App\Tests\DataFixtures\TestSpecific\SetupControllerTestFixtures;
use App\Tests\Functional\AbstractWebTestCase;
use App\Utility\AlphagovNotify\Reference;
use App\Utility\Security\AccountCreationHelper;
use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Symfony\Component\HttpFoundation\Request;

class SetupControllerBehaviourTest extends AbstractWebTestCase
{
    protected ReferenceRepository $fixtureReferenceRepository;
    protected AccountCreationHelper $accountCreationHelper;

    public function setUp(): void
    {
        parent::setUp();
        $databaseTool = static::getContainer()->get(DatabaseToolCollection::class)->get();
        $fixtures = $databaseTool->loadFixtures([SetupControllerTestFixtures::class]);
        $this->fixtureReferenceRepository = $fixtures->getReferenceRepository();
        $this->accountCreationHelper = static::getContainer()->get(AccountCreationHelper::class);
        $this->clearMessages();
    }

    // --- Link Valid ---

    public function testValidLinkInterviewerWithoutPasswordSetPasswordThenLoginForm(): void
    {
        $interviewer = $this->fixtureReferenceRepository->getReference('setup-test:int-no-password', Interviewer::class);
        $url = $this->generateUrl($interviewer);

        $this->submitPreAuth($url);
        $this->submitPasswordForm();

        $this->assertUrlEquals('/login');
    }

    public function testValidLinkDkWithoutPasswordSetPasswordThenDashboard(): void
    {
        $dk = $this->fixtureReferenceRepository->getReference('setup-test:dk-no-password', DiaryKeeper::class);
        $url = $this->generateUrl($dk);

        $this->submitPreAuth($url);
        $this->submitPasswordForm();

        $this->assertUrlEquals('/travel-diary');
    }

    public function testValidLinkInterviewerWithPasswordLoginForm(): void
    {
        $interviewer = $this->fixtureReferenceRepository->getReference('setup-test:int-with-password', Interviewer::class);
        $url = $this->generateUrl($interviewer);

        $this->submitPreAuth($url);

        $this->assertUrlEquals('/login');
    }

    public function testValidLinkDkWithPasswordDashboard(): void
    {
        $dk = $this->fixtureReferenceRepository->getReference('setup-test:dk-with-password', DiaryKeeper::class);
        $url = $this->generateUrl($dk);

        $this->submitPreAuth($url);

        $this->assertUrlEquals('/travel-diary');
    }

    // --- Link Expired/Overused ---

    public function testExpiredLinkInterviewerWithPasswordLoginForm(): void
    {
        $interviewer = $this->fixtureReferenceRepository->getReference('setup-test:int-with-password', Interviewer::class);
        $url = $this->generateUrl($interviewer, expired: true);

        $this->submitPreAuth($url);

        $this->assertUrlEquals('/login');
        $this->assertNoMessages();
    }

    public function testExpiredLinkInterviewerWithoutPasswordResendsEmail(): void
    {
        $interviewer = $this->fixtureReferenceRepository->getReference('setup-test:int-no-password', Interviewer::class);
        $url = $this->generateUrl($interviewer, expired: true);

        $this->submitPreAuth($url);

        $this->assertExpiredPageShown();
        $this->assertAccountCreationEmailSent();
    }

    public function testExpiredLinkDkWithPasswordSteeredToLogin(): void
    {
        $dk = $this->fixtureReferenceRepository->getReference('setup-test:dk-with-password', DiaryKeeper::class);
        $url = $this->generateUrl($dk, expired: true);

        $this->submitPreAuth($url);

        // Activated diary keepers already have a password, so no "create your account" email is resent.
        $this->assertExpiredPageShown();
        $this->assertNoMessages();
    }

    public function testExpiredLinkDkWithoutPasswordResendsEmail(): void
    {
        $dk = $this->fixtureReferenceRepository->getReference('setup-test:dk-no-password', DiaryKeeper::class);
        $url = $this->generateUrl($dk, expired: true);

        $this->submitPreAuth($url);

        $this->assertExpiredPageShown();
        $this->assertAccountCreationEmailSent();
    }

    // --- No-login (purged) user ---

    public function testExpiredLinkNoLoginUserShowsExpiredPage(): void
    {
        $dk = $this->fixtureReferenceRepository->getReference('setup-test:dk-no-login', DiaryKeeper::class);
        $url = $this->generateUrl($dk, expired: true);

        $this->submitPreAuth($url);

        // The error message is slightly different on this page
        self::assertSelectorTextContains('#main-content', 'The link you followed was either invalid or has expired');
        $this->assertNoMessages();
    }

    // --- Link Invalid ---

    public function testInvalidLinkLoginFormWithError(): void
    {
        $url = '/auth/account-setup/bogus-user-id?_signature=tampered&_until=9999999999';

        $this->submitPreAuth($url);

        $this->assertUrlEquals('/login');
    }

    // --- Overused link ---

    public function testOverusedLinkDkWithoutPasswordResendsEmail(): void
    {
        $dk = $this->fixtureReferenceRepository->getReference('setup-test:dk-no-password', DiaryKeeper::class);
        $url = $this->generateUrl($dk);

        // Consume the reuse limit (10 uses) with separate sessions
        for ($i = 0; $i < 10; $i++) {
            $this->client->restart();
            $this->client->request(Request::METHOD_GET, $url);
            $this->client->submitForm('form_continue');
        }

        $this->clearMessages();

        // 11th hit — link is now overused, should behave like expired
        $this->client->restart();
        $this->submitPreAuth($url);

        $this->assertExpiredPageShown();
        $this->assertAccountCreationEmailSent();
    }

    // --- Logged-in reuse exemption ---

    public function testLoggedInUserReusingLinkDoesNotConsumeReuseBudget(): void
    {
        $dk = $this->fixtureReferenceRepository->getReference('setup-test:dk-with-password', DiaryKeeper::class);
        $url = $this->generateUrl($dk);

        // Use the link more times than the 10-use reuse budget, each in a fresh session but
        // authenticated as the link's user. No reuse token should be spent, so the link never
        // becomes overused and no resend email is sent.
        for ($i = 0; $i < 12; $i++) {
            $this->client->restart();
            $this->submitLoginForm('dk-setup-pass@example.com', 'password');
            $this->client->request(Request::METHOD_GET, $url);
            $this->client->submitForm('form_continue');
            $this->assertUrlEquals('/travel-diary');
        }

        $this->assertNoMessages();
    }

    // --- Helpers ---

    protected function generateUrl($userPerson, bool $expired = false): string
    {
        return $this->accountCreationHelper->generateAccountCreationUrl(
            $userPerson,
            currentTime: $expired ? 0 : null,
        );
    }

    protected function submitPreAuth(string $url): void
    {
        $this->client->request(Request::METHOD_GET, $url);
        $this->client->submitForm('form_continue');
    }

    protected function submitPasswordForm(): void
    {
        $password = 'Str0ngP@ssw0rd!';
        $this->client->submitForm('change_password_button_group_save', [
            'change_password[password1]' => $password,
            'change_password[password2]' => $password,
        ]);
    }

    protected function assertExpiredPageShown(): void
    {
        self::assertSelectorTextContains('#main-content', 'The link you followed has expired');
    }

    protected function assertAccountCreationEmailSent(): void
    {
        $message = $this->getMessage(Reference::ACCOUNT_CREATION_EVENT);
        self::assertNotNull($message);
    }

    protected function getMessage(?string $assertEventNameAs = null): object
    {
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);
        $message = $entityManager->getConnection()->fetchAssociative('SELECT * FROM messenger_messages LIMIT 1');

        $this->assertNotFalse($message, 'No message in message table');

        $serializer = $container->get('messenger.default_serializer');
        $envelope = $serializer->decode($message);

        $message = $envelope->getMessage();

        $this->assertInstanceOf(Email::class, $message);
        if ($assertEventNameAs) $this->assertSame($assertEventNameAs, $message->getEventName());
        return $message;
    }

    protected function clearMessages(): void
    {
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);
        $entityManager->getConnection()->executeQuery("DELETE FROM messenger_messages WHERE 'something-to-prevent-delete-warning'=?", ['something-to-prevent-delete-warning']);
        $entityManager->clear();
    }

    protected function assertNoMessages(): void
    {
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);
        $message = $entityManager->getConnection()->fetchAssociative('SELECT * FROM messenger_messages LIMIT 1');
        self::assertFalse($message, 'Expected no messages but found one');
    }
}
