<?php

namespace App\Security\OneTimePassword;

use App\Form\OnBoarding\OtpLoginType;
use App\Utility\TranslatedAuthenticationUtils;
use App\Utility\UrlSigner;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
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
    public const TRAINING_INTERVIEWER_SIG_VERIFIED_REQUEST_KEY = 'interviewer_training.login_verified';

    private FormFactoryInterface $formFactory;
    private PasscodeGenerator $passcodeGenerator;
    private RouterInterface $router;
    private TranslatedAuthenticationUtils $translatedAuthenticationUtils;
    private RequestStack $requestStack;
    private UrlSigner $urlSigner;

    public function __construct(FormFactoryInterface $formFactory, PasscodeGenerator $passcodeGenerator, RouterInterface $router, TranslatedAuthenticationUtils $translatedAuthenticationUtils, RequestStack $requestStack, UrlSigner $urlSigner)
    {
        $this->formFactory = $formFactory;
        $this->passcodeGenerator = $passcodeGenerator;
        $this->router = $router;
        $this->translatedAuthenticationUtils = $translatedAuthenticationUtils;
        $this->requestStack = $requestStack;
        $this->urlSigner = $urlSigner;
    }

    public function supports(Request $request): ?bool
    {
        return $this->isLoginPage($request) && $request->getMethod() === Request::METHOD_POST;
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
        // remove spaces - in order to make them easier to read, we show the passcodes with a space after 4 digits
        $credentials['identifier'] = str_replace(' ', '', $credentials['identifier']);
        $credentials['passcode'] = str_replace(' ', '', $credentials['passcode']);

        $this->translatedAuthenticationUtils->setLastUsername($credentials['identifier'], '_onboarding');

        // We need to verify the Url signature early, in order to prevent the login rate limiter for onboarding (when training)
        if ($credentials['identifier'] === TrainingUserProvider::USER_IDENTIFIER)
        {
            $this->requestStack->getCurrentRequest()->attributes->set(
                self::TRAINING_INTERVIEWER_SIG_VERIFIED_REQUEST_KEY,
                $request->query->has('_interviewer')
                    && $this->urlSigner->isValid($this->requestStack->getCurrentRequest()->getRequestUri())
            );
        }

        return new Passport(
            new UserBadge($credentials['identifier']),
            new CustomCredentials([$this, 'checkCredentials'], $credentials)
        );
    }

    public function checkCredentials(array $credentials): bool
    {
        // The url signature was verified earlier, but we need to use that result here
        $requestAttr = $this->requestStack->getCurrentRequest()->attributes;
        if ($requestAttr->has(self::TRAINING_INTERVIEWER_SIG_VERIFIED_REQUEST_KEY)
            && !$requestAttr->get(self::TRAINING_INTERVIEWER_SIG_VERIFIED_REQUEST_KEY, false)
        ) {
            return false;
        }
        return hash_equals(
            $credentials['passcode'] ?? '',
            $this->passcodeGenerator->getPasswordForUserIdentifier($credentials['identifier'])
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
        return $this->router->generate('onboarding_login', $this->requestStack->getCurrentRequest()->query->all());
    }

    protected function isLoginPage(Request $request): bool
    {
        // We should only check the path, as we might have additional query params that would not match the getLoginUrl()
        return $request->getRequestUri() === $this->getLoginUrl();
    }
}