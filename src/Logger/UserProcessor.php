<?php

namespace App\Logger;

use Monolog\Attribute\AsMonologProcessor;
use Monolog\LogRecord;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;

#[AsMonologProcessor]
class UserProcessor
{
    public function __construct(
        protected TokenStorageInterface $tokenStorage,
    ) {}

    public function __invoke(LogRecord $record): LogRecord
    {
        $token = $this->tokenStorage->getToken();
        $record->extra['_ghost_meta']['user_identifier'] = $token?->getUser()?->getUserIdentifier();
        $record->extra['_ghost_meta']['original_user_identifier'] =
            $token instanceof SwitchUserToken
                ? $token?->getOriginalToken()?->getUser()?->getUserIdentifier()
                : null;

        return $record;
    }
}
