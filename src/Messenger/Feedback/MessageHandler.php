<?php

namespace App\Messenger\Feedback;

use App\Entity\Feedback\Message;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Workflow\WorkflowInterface;

class MessageHandler implements MessageHandlerInterface
{
    public function __construct(
        protected EntityManagerInterface $entityManager, protected WorkflowInterface $feedbackMessageStateMachine
    ) {}

    public function __invoke(AbstractMessage $message): void
    {
        if ($message instanceof AssignFeedbackMessage) {
            $feedbackMessage = $this->entityManager->find(Message::class, $message->getFeedbackMessageId());
            $this->feedbackMessageStateMachine->apply(
                $feedbackMessage,
                Message::TRANSITION_ASSIGN,
                [Message::TRANSITION_CONTEXT_ASSIGN_TO => $message->getAssignTo()]
            );
            $this->entityManager->flush();
        }
    }
}
