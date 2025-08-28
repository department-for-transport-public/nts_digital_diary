<?php

namespace App\Security\Test;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;

class TestAuthenticator extends AbstractAuthenticator
{
    public function __construct(protected string $appEnvironment, protected RouterInterface $router, protected TokenStorageInterface $tokenStorage)
    {
    }

    public function supports(Request $request): ?bool
    {
        return $this->appEnvironment === 'test' && $request->getSession()->get('_security_test_login');
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function isInteractive(): bool
    {
        return false;
    }

    public function authenticate(Request $request): Passport
    {
        $userIdentifier = $request->getSession()->get('_security_test_login');
        return new SelfValidatingPassport(new UserBadge($userIdentifier));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        if ($request->hasSession()) {
            $request->getSession()->set(SecurityRequestAttributes::AUTHENTICATION_ERROR, $exception);
        }

        return new RedirectResponse($this->router->generate('app_login'));
    }
}
