<?php

namespace App\Tests\EventSubscriber\Feedback;

use App\Entity\Feedback\Message;
use App\Tests\DataFixtures\TestSpecific\FeedbackAssignedSubscriberTestFixtures;
use Symfony\Component\Workflow\Exception\LogicException;

class MessageAssignedSubscriberTest extends AbstractFeedbackSubscriberTest
{
    public function setUp(): void
    {
        parent::loadFixturesAndDoSetUp([FeedbackAssignedSubscriberTestFixtures::class]);
    }

    public function dataMessageChange(): array
    {
        return [
            'Message assigned' => [
                true,  // will dispatch email?
                false, // will exception?
                'feedback-message:not-assigned',
                'someone',
            ],
            'Message assigned to new person' => [
                true,  // will dispatch email?
                false, // will exception?
                'feedback-message:assigned',
                'new-person',
            ],
            'Message assigned but to same person as before' => [
                false, // will dispatch email?
                true,  // will exception?
                'feedback-message:assigned',
                'original',
            ],
        ];
    }

    /**
     * @dataProvider dataMessageChange
     */
    public function testMessageChange(bool $expectedToDispatch, bool $exceptionExpected, string $messageReference, string $assignTo): void
    {
        $message = $this->referenceRepository->getReference($messageReference);
        $this->assertInstanceOf(Message::class, $message);

        $invocationRule = $expectedToDispatch ?
            $this->once() :
            $this->never();

        $this->notifyHelperMock
            ->expects($invocationRule)
            ->method('sendFeedbackAssignedMessage');

        if ($exceptionExpected) {
            $this->expectException(LogicException::class);
        }

        $this->stateMachine->apply($message, Message::TRANSITION_ASSIGN, [Message::TRANSITION_CONTEXT_ASSIGN_TO => $assignTo]);
    }
}