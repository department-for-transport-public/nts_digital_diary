<?php

namespace App\Messenger\AlphagovNotify;

use Alphagov\Notifications\Client;
use Alphagov\Notifications\Exception\ApiException;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class MessageHandler implements MessageHandlerInterface
{
    private Client $alphagovNotify;

    public function __construct(Client $alphagovNotify)
    {
        $this->alphagovNotify = $alphagovNotify;
    }

    public function __invoke(AbstractMessage $message)
    {
        try {
            switch(get_class($message)) {
                case Email::class :
                    return $this->alphagovNotify->sendEmail(...$message->getSendMethodParameters());

                case Sms::class :
                    return $this->alphagovNotify->sendSms(...$message->getSendMethodParameters());

                case Letter::class :
                    return $this->alphagovNotify->sendLetter(...$message->getSendMethodParameters());

                default :
                    throw new \UnexpectedValueException(get_class($message));
            }
        } catch (ApiException $apiException) {
            if (in_array($apiException->getCode(), [400, 403])) {
                throw new UnrecoverableMessageHandlingException(null, null, $apiException);
            }
            throw $apiException;
        }
    }
}
