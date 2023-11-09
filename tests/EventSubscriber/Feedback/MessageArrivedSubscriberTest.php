<?php

namespace App\Tests\EventSubscriber\Feedback;

use App\Entity\Feedback\Message;
use Doctrine\DBAL\Exception\NotNullConstraintViolationException;

class MessageArrivedSubscriberTest extends AbstractFeedbackSubscriberTest
{
    public function setUp(): void
    {
        parent::loadFixturesAndDoSetUp([]);
    }

    public function testMessageTriggeredWhenFeedback(): void
    {
        $this->notifyHelperMock
            ->expects($this->once())
            ->method('sendFeedbackArrivedMessage');

        $message = (new Message())
            ->setMessage('Banana')
            ->setPage('/');

        $this->entityManager->persist($message);
        $this->entityManager->flush();
    }

    public function testMessageNotTriggeredWhenFlushFails(): void
    {
        $this->notifyHelperMock
            ->expects($this->never())
            ->method('sendFeedbackArrivedMessage');

        $message = new Message();

        $this->entityManager->persist($message);

        // Since we have not set page, it is null and will fail to save, triggering an exception
        // More importantly, a message will not be dispatched (hopefully)

        $this->expectException(NotNullConstraintViolationException::class);
        $this->entityManager->flush();
    }
}