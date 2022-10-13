<?php

namespace App\Tests\Functional\Interviewer;

use App\Entity\User;
use App\Messenger\AlphagovNotify\Email;
use App\Tests\DataFixtures\UserFixtures;
use App\Tests\Functional\AbstractWebTestCase;
use App\Utility\Test\CrawlerTableHelper;
use App\Utility\Test\MessageUrlRetriever;
use Doctrine\ORM\EntityManagerInterface;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class EmailChangeTest extends AbstractWebTestCase
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
        // Log in as an interviewer
        $this->client->request('GET', '/');
        $this->submitLoginForm('interviewer@example.com', 'password');
        $this->assertEquals('/interviewer', $this->client->getRequest()->getRequestUri());

        // Fetch a particular user
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $user = $entityManager
            ->getRepository(User::class)
            ->findOneBy(['username' => 'diary-keeper-no-password@example.com']);

        // Drill down to find this user in areas > households
        $diaryKeeper = $user->getDiaryKeeper();
        $household = $diaryKeeper->getHousehold();
        $areaPeriod = $household->getAreaPeriod();

        $tableHelper = new CrawlerTableHelper($this->client->getCrawler());
        $viewAreaUrl = $tableHelper->getLinkUrlForRowMatching('View', [
            'Area ID' => "".$areaPeriod->getArea(),
        ], false);
        $this->client->request('GET', $viewAreaUrl);

        $tableHelper = new CrawlerTableHelper($this->client->getCrawler());
        $viewHouseholdUrl = $tableHelper->getLinkUrlForRowMatching('View', [
            'Serial' => $areaPeriod->getArea().' / '.str_pad($household->getAddressNumber(), 2, '0', STR_PAD_LEFT).' / '.$household->getHouseholdNumber(),
        ], false);
        $this->client->request('GET', $viewHouseholdUrl);

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
