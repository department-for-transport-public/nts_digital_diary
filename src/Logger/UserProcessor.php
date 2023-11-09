<?php

namespace App\Logger;

use Monolog\Attribute\AsMonologProcessor;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

#[AsMonologProcessor]
class UserProcessor
{
    public function __construct(
        protected TokenStorageInterface $tokenStorage,
    ) {}

    public function __invoke(array $context): array
    {
        $token = $this->tokenStorage->getToken();
        $context['context']['_ghost_meta']['user_identifier'] = $token?->getUser()?->getUserIdentifier();

        return $context;
    }
}