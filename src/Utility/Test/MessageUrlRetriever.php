<?php

namespace App\Utility\Test;

use App\Messenger\AlphagovNotify\Email;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class MessageUrlRetriever
{
    public static function getUrlFromMessage(ContainerInterface $container, TestCase $testCase): string
    {
        $entityManager = $container->get(EntityManagerInterface::class);
        $message = $entityManager->getConnection()->fetchAssociative('SELECT * FROM messenger_messages LIMIT 1');

        $serializer = $container->get('messenger.default_serializer');
        $envelope = $serializer->decode($message);

        $message = $envelope->getMessage();

        $testCase->assertInstanceOf(Email::class, $message);
        return $message->getPersonalisation()['url'];
    }
}