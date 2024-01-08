<?php

namespace App\Utility\Security;

use LogicException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\TooManyLoginAttemptsAuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Couldn't extend the existing class, as it's got "private" sprayed everywhere.
 *
 * This version provides two extra features:
 * a) The ability to have a last-username stored under a suffixed session key
 *    (i.e. different last-usernames for different login pages)
 * b) Translations of error messages, with translation keys being prefixed
 */
class TranslatedAuthenticationUtils
{
    protected TranslatorInterface $translator;
    protected RequestStack $requestStack;

    public function __construct(TranslatorInterface $translator, RequestStack $requestStack)
    {
        $this->translator = $translator;
        $this->requestStack = $requestStack;
    }

    public function getLastAuthenticationErrorMessage(string $translationPrefix, bool $clearSession = true): ?string
    {
        $error = $this->getLastAuthenticationError($clearSession);
        if (!$error instanceof AuthenticationException) {
            return null;
        }

        if ($error instanceof TooManyLoginAttemptsAuthenticationException) {
            $errorMessage = $this->translator->trans("{$translationPrefix}.too-many-attempts", $error->getMessageData(), 'validators');
        } else {
            $errorMessage = strtr($error->getMessageKey(), $error->getMessageData());
            $errorMap = [
                'Invalid credentials.' => "{$translationPrefix}.invalid-credentials",
                'Bad credentials.' => "{$translationPrefix}.invalid-credentials",
                'Invalid CSRF token.' => "{$translationPrefix}.csrf-error",
                'The presented password cannot be empty.' => "{$translationPrefix}.invalid-credentials",
                'The presented password is invalid.' => "{$translationPrefix}.invalid-credentials",
            ];

            $errorMessageKey = $errorMap[$errorMessage] ?? null;
            if ($errorMessageKey) {
                $errorMessage = $this->translator->trans($errorMessageKey, [], 'validators');
            }
        }

        return $errorMessage;
    }

    public function getLastAuthenticationError(bool $clearSession = true): ?AuthenticationException
    {
        $request = $this->getRequest();
        $authenticationException = null;

        if ($request->attributes->has(Security::AUTHENTICATION_ERROR)) {
            $authenticationException = $request->attributes->get(Security::AUTHENTICATION_ERROR);
        } elseif ($request->hasSession() && ($session = $request->getSession())->has(Security::AUTHENTICATION_ERROR)) {
            $authenticationException = $session->get(Security::AUTHENTICATION_ERROR);

            if ($clearSession) {
                $session->remove(Security::AUTHENTICATION_ERROR);
            }
        }

        return $authenticationException;
    }

    public function getLastUsername(?string $sessionKeySuffix=null): string
    {
        $request = $this->getRequest();
        $sessionKey = $this->getSessionKey($sessionKeySuffix);

        if ($request->attributes->has($sessionKey)) {
            return $request->attributes->get($sessionKey, '');
        }

        return $request->hasSession() ? $request->getSession()->get($sessionKey, '') : '';
    }

    public function setLastUsername(?string $lastUsername, ?string $sessionKeySuffix=null): void
    {
        $session = $this->requestStack->getSession();
        $sessionKey = $this->getSessionKey($sessionKeySuffix);

        if ($lastUsername === null) {
            $session->remove($sessionKey);
        } else {
            $session->set($sessionKey, $lastUsername);
        }
    }

    private function getRequest(): Request
    {
        $request = $this->requestStack->getCurrentRequest();

        if (null === $request) {
            throw new LogicException('Request should exist so it can be processed for error.');
        }

        return $request;
    }

    protected function getSessionKey(?string $sessionKeySuffix): string
    {
        return Security::LAST_USERNAME . ($sessionKeySuffix ?? '');
    }
}