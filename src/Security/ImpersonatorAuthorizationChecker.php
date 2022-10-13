<?php

namespace App\Security;

use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\NullToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;

class ImpersonatorAuthorizationChecker implements AuthorizationCheckerInterface
{
    private TokenStorageInterface $tokenStorage;
    private AccessDecisionManagerInterface $accessDecisionManager;
    private bool $exceptionOnNoToken;

    public function __construct(TokenStorageInterface $tokenStorage, AccessDecisionManagerInterface $accessDecisionManager, bool $exceptionOnNoToken = false)
    {
        $this->tokenStorage = $tokenStorage;
        $this->accessDecisionManager = $accessDecisionManager;
        $this->exceptionOnNoToken = $exceptionOnNoToken;
    }

    /**
     * {@inheritdoc}
     *
     * @throws AuthenticationCredentialsNotFoundException when the token storage has no authentication token and $exceptionOnNoToken is set to true
     */
    final public function isGranted($attribute, $subject = null): bool
    {
        $token = $this->tokenStorage->getToken();

        $impersonatorToken = ($token instanceof SwitchUserToken) ?
            $token->getOriginalToken() :
            null;

        if ($impersonatorToken === null) {
            if ($this->exceptionOnNoToken) {
                throw new AuthenticationCredentialsNotFoundException('The token storage contains no authentication token. One possible reason may be that there is no firewall configured for this URL.');
            }

            $impersonatorToken = new NullToken();
        }

        return $this->accessDecisionManager->decide($impersonatorToken, [$attribute], $subject);
    }
}
