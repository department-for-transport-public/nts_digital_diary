<?php

namespace App\Security\OneTimePassword;

use App\Entity\OtpUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\CustomCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Twig\Environment;

class DirectOnboardingAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    public function __construct(
        protected PasscodeGenerator $passcodeGenerator,
        protected RouterInterface $router,
        protected Security $security,
        protected Environment $twig,
    ) {}

    public function supports(Request $request): ?bool
    {
        return $request->getPathInfo() === $this->router->generate('onboarding_direct_auth_index')
            && $request->getMethod() === Request::METHOD_POST
            && $request->query->has('passcode1')
            && $request->query->has('passcode2');
    }

    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        // As this is not the entry-point for the firewall, this should not get called
        return new RedirectResponse($this->getLoginUrl());
    }

    public function authenticate(Request $request): Passport
    {
        $credentials['identifier'] = $request->query->get('passcode1');
        $credentials['passcode'] = $request->query->get('passcode2');

        return new Passport(
            new UserBadge($credentials['identifier']),
            new CustomCredentials($this->checkCredentials(...), $credentials)
        );
    }

    public function checkCredentials(array $credentials): bool
    {
        return hash_equals(
            $credentials['passcode'] ?? '',
            $this->passcodeGenerator->getPasswordForUserIdentifier($credentials['identifier'])
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        /** @var OtpUser $user */
        $user = $token->getUser();
        if ($user->getHousehold()?->getIsOnboardingComplete()) {
            $this->security->logout(false);
            return (new Response())
                ->setStatusCode(Response::HTTP_FORBIDDEN)
                ->setContent($this->twig->render('on_boarding/direct_onboarding/credentials-already-used.html.twig', [
                    'household' => $user->getHousehold(),
                    'passcode1' => $user->getUserIdentifier(),
                ]))
                ;
        }

        $request->query->remove('passcode1');
        $request->query->remove('passcode2');
        return new RedirectResponse($this->router->generate('onboarding_direct_workflow_index', $request->query->all()));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        if ($request->hasSession()) {
            $request->getSession()->set(SecurityRequestAttributes::AUTHENTICATION_ERROR, $exception);
        }
        throw new AccessDeniedHttpException();
    }

    public function isInteractive(): bool
    {
        return false;
    }

    protected function getLoginUrl(): string
    {
        return $this->router->generate('onboarding_login');
    }
}
