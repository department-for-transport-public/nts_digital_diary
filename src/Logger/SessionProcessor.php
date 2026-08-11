<?php

namespace App\Logger;

use Monolog\Attribute\AsMonologProcessor;
use Monolog\LogRecord;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

#[AsMonologProcessor]
class SessionProcessor
{
    public function __construct(
        protected RequestStack $requestStack,
        protected TokenStorageInterface $tokenStorage,
    ) {}

    public function __invoke(LogRecord $logRecord): LogRecord
    {
        $request = $this->requestStack->getCurrentRequest();

        if ($request) {
            // truncate session IDs to prevent hijacking
            $sId = $request->cookies->get('PHPSESSID', null);
            $logRecord->extra['_ghost_meta']['session'] = $sId ? substr($sId, 0, 16) : null;
        }

        return $logRecord;
    }
}
