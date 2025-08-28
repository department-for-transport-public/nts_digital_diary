<?php

namespace App\Tests\Functional\Auth;

use App\Messenger\AlphagovNotify\Email;
use App\Tests\DataFixtures\UserFixtures;
use App\Tests\Functional\AbstractWebTestCase;
use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Symfony\Component\HttpFoundation\Request;

abstract class AbstractAuthTestCase extends AbstractWebTestCase
{
    protected ReferenceRepository $fixtureReferenceRepository;
    public function setUp(): void
    {
        parent::setUp();
        $databaseTool = static::getContainer()->get(DatabaseToolCollection::class)->get();
        $fixtures = $databaseTool->loadFixtures([
            UserFixtures::class
        ]);
        $this->fixtureReferenceRepository = $fixtures->getReferenceRepository();
    }

    protected function submitPasswordResetForm(string $emailAddress): void
    {
        $this->client->request(Request::METHOD_GET, '/auth/forgotten-password');
        $this->client->submitForm('Reset password', ['forgotten_password[emailAddress]' => $emailAddress]);
    }

    protected function getUrlFromMessage(?string $assertEventNameAs = null): string
    {
        return $this->getMessage($assertEventNameAs)->getPersonalisation()['url'];
    }

    protected function getAddresseeFromMessage(?string $assertEventNameAs = null): string
    {
        return $this->getMessage($assertEventNameAs)->getEmailAddress();
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
}