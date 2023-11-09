<?php

namespace App\EventSubscriber\Feedback;

use App\Entity\Feedback\Group;
use App\Entity\Feedback\Message;
use App\Security\GoogleIap\AdminRoleResolver;
use App\Utility\Feedback\NotifyHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\CompletedEvent;
use Symfony\Component\Workflow\Exception\LogicException;

class MessageAssignedSubscriber implements EventSubscriberInterface
{
    public function __construct(
        protected AdminRoleResolver $adminRoleResolver,
        protected NotifyHelper $notifyHelper,
    ) {}

    public function assignCompleted(CompletedEvent $event): void
    {
        /** @var Message $subject */
        $subject = $event->getSubject();
        $context = $event->getContext();

        $assignee = $context[Message::TRANSITION_CONTEXT_ASSIGN_TO] ?? null;
        if ($assignee === null) {
            throw new LogicException('"assignee" must be provided in transition context');
        }

        $assigneeGroup = $this->adminRoleResolver->getAssignee($assignee);

        if (!$assigneeGroup instanceof Group) {
            // This shouldn't happen anywhere except unit testing with bad mocks
            throw new LogicException("Unknown Assignee '{$assignee}'");
        }

        foreach ($assigneeGroup->getNotificationEmails() as $recipient) {
            $this->notifyHelper->sendFeedbackAssignedMessage($recipient, $subject->getId());
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.feedback_message.completed.assign' => 'assignCompleted',
        ];
    }
}