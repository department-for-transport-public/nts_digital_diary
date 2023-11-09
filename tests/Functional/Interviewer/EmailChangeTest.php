<?php

namespace App\Tests\Functional\Interviewer;

use App\Entity\DiaryKeeper;
use App\Messenger\AlphagovNotify\Email;
use App\Tests\DataFixtures\UserFixtures;
use App\Utility\Test\MessageUrlRetriever;
use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class EmailChangeTest extends AbstractInterviewerDiaryKeeperTest
{
    protected KernelBrowser $client;
    private ReferenceRepository $repository;

    public function setUp(): void
    {
        parent::setUp();
        $databaseTool = static::getContainer()->get(DatabaseToolCollection::class)->get();
        $fixtures = $databaseTool->loadFixtures([
            UserFixtures::class
        ]);
        $this->repository = $fixtures->getReferenceRepository();
    }

    public function testPasswordReset(): void
    {
        $newEmailAddress = "new-email-address@example.com";

        /** @var DiaryKeeper $dk */
        $dk = $this->repository->getReference('diary-keeper:no-password');
        $this->logInAsInterviewerAndDrillDownToUsersHouseholdPage($dk->getUser()->getUserIdentifier());
        $this->client->clickLink("Details / actions: for {$dk->getName()}");
        $this->client->clickLink('Change email');

        $this->client->submitForm('Change email address', [
            'change_email[emailAddress]' => $newEmailAddress,
        ]);

        // ensure "pending change" flag is present
        $this->assertStringContainsString('change pending', $this->client->getCrawler()->text());

        // Logout, and then visit the reset URL that can be retrieved from messenger
        $this->client->clickLink('Logout');

        $passwordResetUrl = MessageUrlRetriever::getUrlFromMessage(self::getContainer(), $this);
        $this->client->request('GET', $passwordResetUrl);

        // Set a new password
        $this->client->submitForm('Create account', [
            'change_password[password1]' => 'Banana1234',
            'change_password[password2]' => 'Banana1234',
        ]);

        $this->assertEquals('/login', $this->client->getRequest()->getRequestUri());

        // Verify that old email + old password, old email + new password do not work
        $this->submitLoginForm('diary-keeper-no-password@example.com', 'password');
        $this->assertEquals('/login', $this->client->getRequest()->getRequestUri());

        $this->submitLoginForm('diary-keeper-no-password@example.com', 'Banana1234');
        $this->assertEquals('/login', $this->client->getRequest()->getRequestUri());

        // Verify that new email + new password works...
        $this->submitLoginForm($newEmailAddress, 'Banana1234');
        $this->assertEquals('/travel-diary', $this->client->getRequest()->getRequestUri());

        // Log back in as interviewer to confirm "pending change" flag has gone
        $this->client->clickLink('Logout');
        $this->logInAsInterviewerAndDrillDownToUsersHouseholdPage($newEmailAddress);
        $this->client->clickLink("Details / actions: for {$dk->getName()}");
        $this->assertStringNotContainsString('change pending', $this->client->getCrawler()->text());
    }

    protected function getPasswordResetUrlFromMessage(): string
    {
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);
        $message = $entityManager->getConnection()->fetchAssociative('SELECT * FROM messenger_messages LIMIT 1');

        $serializer = $container->get('messenger.default_serializer');
        $envelope = $serializer->decode($message);

        $message = $envelope->getMessage();

        $this->assertInstanceOf(Email::class, $message);
        return $message->getPersonalisation()['url'];
    }
}
