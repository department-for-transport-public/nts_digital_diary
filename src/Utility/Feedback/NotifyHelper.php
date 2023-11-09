<?php

namespace App\Utility\Feedback;

use App\Entity\Feedback\Message;
use App\Messenger\AlphagovNotify\Email;
use App\Twig\FormatExtension;
use App\Utility\AlphagovNotify\Reference;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class NotifyHelper
{
    public function __construct(
        protected MessageBusInterface $messageBus,
        protected RouterInterface $router,
        protected string $appEnvironment,
        protected EntityManagerInterface $entityManager,
        protected FormatExtension $formatExtension,
    ) {}

    public function sendFeedbackArrivedMessage(string $recipient, Message $message): void
    {
        if ($this->appEnvironment === 'test') {
            return;
        }

        $url = $this->router->generate('admin_feedback_assignment_message', ['message' => $message->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        $this->messageBus->dispatch(new Email(
            Reference::FEEDBACK_CENTRE_MESSAGE_ARRIVED,
            Message::class,
            $message->getId(),
            $recipient,
            Reference::FEEDBACK_CENTRE_MESSAGE_ARRIVED_TEMPLATE_ID,
            [
                'url' => $url,
                'category' => $message->getCategory(),
            ]
        ));
    }

    public function sendFeedbackAssignedMessage(string $recipient, string $messageId): void
    {
        if ($this->appEnvironment === 'test') {
            return;
        }

//        $url = $this->router->generate('admin_feedback_message_view', ['message' => $messageId], UrlGeneratorInterface::ABSOLUTE_URL);
        $message = $this->entityManager->find(Message::class, $messageId);

        $this->messageBus->dispatch(new Email(
            Reference::FEEDBACK_CENTRE_MESSAGE_ASSIGNED,
            Message::class,
            $messageId,
            $recipient,
            Reference::FEEDBACK_CENTRE_MESSAGE_ASSIGNED_TEMPLATE_ID,
            [
                'message' => $message->getMessage(),
                'messageEmailAddress' => $message->getEmailAddressOrAnon(),
                'messageId' => $messageId,
                'assignedTo' => $message->getAssignedTo(),
                'page' => $message->getPageOrUnknown(),
                'userSerial' => $this->formatExtension->formatFeedbackUserSerial($message->getCurrentUserSerial()),
                'impersonatorSerial' => $this->formatExtension->formatFeedbackUserSerial($message->getOriginalUserSerial()),
                'category' => $message->getCategory(),
            ]
        ));
    }
}