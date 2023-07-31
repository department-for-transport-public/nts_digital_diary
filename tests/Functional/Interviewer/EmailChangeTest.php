<?php

namespace App\Tests\Functional\Interviewer;

use App\Messenger\AlphagovNotify\Email;
use App\Tests\DataFixtures\UserFixtures;
use App\Utility\Test\CrawlerTableHelper;
use App\Utility\Test\MessageUrlRetriever;
use Doctrine\ORM\EntityManagerInterface;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class EmailChangeTest extends AbstractInterviewerDiaryKeeperTest
{
    protected KernelBrowser $client;

    public function setUp(): void
    {
        parent::setUp();
        $databaseTool = static::getContainer()->get(DatabaseToolCollection::class)->get();
        $databaseTool->loadFixtures([
            UserFixtures::class
        ]);
    }

    public function testPasswordReset(): void
    {
        $this->logInAsInterviewerAndDrillDownToUsersHouseholdPage('diary-keeper-no-password@example.com');

        // Click the "change email" link and add a new email address
        $tableHelper = new CrawlerTableHelper($this->client->getCrawler());
        $changeEmailUrl = $tableHelper->getLinkUrlForRowMatching('Change email', [
            'Name' => 'Test Diary Keeper (No password set)',
        ]);
        $this->client->request('GET', $changeEmailUrl);

        $this->client->submitForm('Change email address', [
            'change_email[emailAddress]' => 'new-email-address@example.com',
        ]);

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
        $this->submitLoginForm('new-email-address@example.com', 'Banana1234');
        $this->assertEquals('/travel-diary', $this->client->getRequest()->getRequestUri());
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
