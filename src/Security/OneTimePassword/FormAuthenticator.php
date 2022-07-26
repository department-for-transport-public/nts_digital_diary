<?php

namespace App\Security\OneTimePassword;

use App\Form\OnBoarding\OtpLoginType;
use App\Utility\TranslatedAuthenticationUtils;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\InteractiveAuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\CustomCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class FormAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface, InteractiveAuthenticatorInterface
{
    private FormFactoryInterface $formFactory;
    private PasscodeGenerator $passcodeGenerator;
    private RouterInterface $router;
    private TranslatedAuthenticationUtils $translatedAuthenticationUtils;

    public function __construct(FormFactoryInterface $formFactory, PasscodeGenerator $passcodeGenerator, RouterInterface $router, TranslatedAuthenticationUtils $translatedAuthenticationUtils)
    {
        $this->formFactory = $formFactory;
        $this->passcodeGenerator = $passcodeGenerator;
        $this->router = $router;
        $this->translatedAuthenticationUtils = $translatedAuthenticationUtils;
    }

    public function supports(Request $request): ?bool
    {
        return ($this->isLoginPage($request) && $request->getMethod() === Request::METHOD_POST);
    }

    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        // As this is not the entry-point for the firewall, this should not get called
        return new RedirectResponse($this->getLoginUrl());
    }

    public function authenticate(Request $request): Passport
    {
        $form = $this->formFactory->create(OtpLoginType::class);
        $form->handleRequest($request);

        if (!$form->isValid()) {
            $errors = $form->getErrors(true, true);
            foreach($errors as $error) {
                if ($error->getCause() instanceof CsrfToken) {
                    throw new AuthenticationException("Invalid CSRF token.");
                }
            }

            throw new AuthenticationException("Bad credentials.");
        }

        $credentials = $form->getData();
        $this->translatedAuthenticationUtils->setLastUsername($credentials['identifier'], '_onboarding');

        return new Passport(
            new UserBadge($credentials['identifier']),
            new CustomCredentials(
                fn($credentials) => hash_equals(
                    $credentials['passcode'] ?? '',
                    $this->passcodeGenerator->getPasswordForUserIdentifier($credentials['identifier'])
                ),
                $credentials
            )
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return new RedirectResponse($this->router->generate('onboarding_dashboard'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        if ($request->hasSession()) {
            $request->getSession()->set(Security::AUTHENTICATION_ERROR, $exception);
        }

        return new RedirectResponse($this->getLoginUrl());
    }

    public function isInteractive(): bool
    {
        return true;
    }

    protected function getLoginUrl(): string
    {
        return $this->router->generate('onboarding_login');
    }

    protected function isLoginPage(Request $request): bool
    {
        return $request->getRequestUri() === $this->getLoginUrl();
    }
}