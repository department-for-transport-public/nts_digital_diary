<?php

namespace App\Tests\Functional\Auth;

use App\Messenger\AlphagovNotify\Email;
use App\Tests\DataFixtures\UserFixtures;
use App\Tests\Functional\AbstractWebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;

abstract class AbstractAuthTestCase extends AbstractWebTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $databaseTool = static::getContainer()->get(DatabaseToolCollection::class)->get();
        $databaseTool->loadFixtures([
            UserFixtures::class
        ]);
    }

    protected function submitPasswordResetForm(string $emailAddress): void
    {
        $this->client->request('GET', '/auth/forgotten-password');
        $this->client->submitForm('Reset password', ['forgotten_password[emailAddress]' => $emailAddress]);
    }

    protected function getPasswordResetUrlFromMessage(): string
    {
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);
        $message = $entityManager->getConnection()->fetchAssociative('SELECT * FROM messenger_messages LIMIT 1');

        $this->assertNotFalse($message, 'No message in message table');

        $serializer = $container->get('messenger.default_serializer');
        $envelope = $serializer->decode($message);

        $message = $envelope->getMessage();

        $this->assertInstanceOf(Email::class, $message);
        $this->assertSame('forgotten-password', $message->getEventName());
        return $message->getPersonalisation()['url'];
    }
}