<?php

namespace App\EventSubscriber;

use Alphagov\Notifications\Exception\ApiException;
use App\Entity\DiaryKeeper;
use App\Entity\UserPersonInterface;
use App\Entity\Utility\NotifyApiResponse;
use App\Messenger\AlphagovNotify\AbstractMessage;
use App\Utility\AlphagovNotify\Reference;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Messenger\Event\WorkerMessageHandledEvent;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Workflow\Registry;

class AlphagovNotifyMessengerSubscriber implements EventSubscriberInterface
{
    public const STATUS_KEY = 'symfony_messenger_bus.govuk_notify_status';

    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected Registry $workflowRegistry
    ) {}

    public function onMessageFailed(WorkerMessageFailedEvent $event): void
    {
        $originalMessage = $event->getEnvelope()->getMessage();
        if (!$originalMessage instanceof AbstractMessage) {
            return;
        }

        $notifyApiEvent = $event->getThrowable()->getPrevious();
        if ($notifyApiEvent instanceof UnrecoverableMessageHandlingException) {
            $notifyApiEvent = $notifyApiEvent->getPrevious();
        }

        if ($notifyApiEvent instanceof ApiException) {
            $apiResponseData = [
                'code' => $notifyApiEvent->getCode(),
                'errors' => $notifyApiEvent->getErrors(),
                self::STATUS_KEY => $event->willRetry()
                    ? Reference::STATUS_WAITING_FOR_RETRY
                    : Reference::STATUS_FAILED,
            ];
        } else {
            // We've got some other exception ?!
            $apiResponseData = [
                'code' => $notifyApiEvent->getCode(),
                'errors' => [
                    [
                        'error' => $notifyApiEvent->getMessage(),
                        'message' => $notifyApiEvent->getTraceAsString(),
                    ],
                ],
                self::STATUS_KEY => Reference::STATUS_FAILED,
            ];
        }

        $this->logResponse($originalMessage, $apiResponseData);
    }

    public function onMessageHandled(WorkerMessageHandledEvent $event): void
    {
        $originalMessage = $event->getEnvelope()->getMessage();
        if (!$originalMessage instanceof AbstractMessage) {
            return;
        }

        /** @var HandledStamp $stamp */
        $stamp = $event->getEnvelope()->last(HandledStamp::class);
        $apiResponseData = $stamp->getResult();
        $apiResponseData[self::STATUS_KEY] = Reference::STATUS_ACCEPTED;
        $this->logResponse($originalMessage, $apiResponseData);
    }

    protected function logResponse(AbstractMessage $message, array $apiResponseData): void
    {
        $timestamp = new \DateTime();

        $notifyApiResponse = (new NotifyApiResponse())
            ->setEventName($message->getEventName())
            ->setMessageClass($message::class)
            ->setData($apiResponseData)
            ->setRecipient($message->getRecipient())
            ->setRecipientHash($message->getRecipientHash())
            ->setTimestamp($timestamp);

        $this->entityManager->persist($notifyApiResponse);

        /** @var UserPersonInterface $entity */
        $entity = $this->entityManager->find($message->getOriginatingEntityClass(), $message->getOriginatingEntityId());
        $user = match($message->getOriginatingEntityClass()) {
            DiaryKeeper::class => $entity?->getUser(),
            default => null,
        };

        if ($user) {
            $user->addNotifyApiResponse($notifyApiResponse);
        }

        $this->entityManager->flush();
    }

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [
            WorkerMessageFailedEvent::class => ['onMessageFailed', -512],
            WorkerMessageHandledEvent::class => ['onMessageHandled', -512],
        ];
    }
}