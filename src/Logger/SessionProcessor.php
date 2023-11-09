<?php

namespace App\Logger;

use Monolog\Attribute\AsMonologProcessor;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

#[AsMonologProcessor]
class SessionProcessor
{
    public function __construct(
        protected RequestStack $requestStack,
        protected TokenStorageInterface $tokenStorage,
    ) {}

    public function __invoke(array $context): array
    {
        $request = $this->requestStack->getCurrentRequest();

        if ($request) {
            $context['context']['_ghost_meta']['session'] = $request->cookies->get('PHPSESSID', null);
        }

        return $context;
    }
}