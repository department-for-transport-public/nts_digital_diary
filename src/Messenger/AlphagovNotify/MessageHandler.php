<?php

namespace App\Messenger\AlphagovNotify;

use Alphagov\Notifications\Client;
use Alphagov\Notifications\Exception\ApiException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;

#[AsMessageHandler]
class MessageHandler
{
    public function __construct(protected readonly Client $alphagovNotify)
    {}
    public function __invoke(AbstractMessage $message)
    {
        try {
            switch($message::class) {
                case Email::class :
                    return $this->alphagovNotify->sendEmail(...$message->getSendMethodParameters());

                case Sms::class :
                    return $this->alphagovNotify->sendSms(...$message->getSendMethodParameters());

                case Letter::class :
                    return $this->alphagovNotify->sendLetter(...$message->getSendMethodParameters());

                default :
                    throw new \UnexpectedValueException($message::class);
            }
        } catch (ApiException $apiException) {
            if (in_array($apiException->getCode(), [400, 403])) {
                throw new UnrecoverableMessageHandlingException(null, null, $apiException);
            }
            throw $apiException;
        }
    }
}
