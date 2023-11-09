<?php

namespace App\Tests\DataFixtures\TestSpecific;

use App\Entity\Feedback\Message;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;

class FeedbackAssignedSubscriberTestFixtures extends AbstractFixture
{
    public function load(ObjectManager $manager): void
    {
        $messageOne = (new Message())
            ->setMessage('This is message #1')
            ->setState(Message::STATE_NEW)
            ->setPage('/');

        $manager->persist($messageOne);
        $this->addReference('feedback-message:not-assigned', $messageOne);

        $messageTwo = (new Message())
            ->setMessage('This is message #2')
            ->setState(Message::STATE_ASSIGNED, [Message::TRANSITION_CONTEXT_ASSIGN_TO => 'original'])
            ->setPage('/');

        $manager->persist($messageTwo);
        $this->addReference('feedback-message:assigned', $messageTwo);

        $manager->flush();
    }
}