<?php

namespace App\Security\HmacAuth;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;

/**
 * The HMAC v1 authenticator was removed. This matches the v1 request shape purely
 * to return a clear "v1 is gone, use v2" error, instead of the generic
 * "Full authentication is required" produced when no authenticator matches.
 */
class HmacAuthenticatorV1 extends AbstractAuthenticator
{
    public function supports(Request $request): ?bool
    {
        return
            !$request->headers->has('X-AUTH-VERSION') // v1 sent no version header
            && $request->headers->has('X-AUTH-KEY')
            && $request->headers->has('X-AUTH-SIGNATURE')
            && $request->query->has('timestamp');
    }

    public function authenticate(Request $request): Passport
    {
        throw new CustomUserMessageAuthenticationException(
            'HMAC authentication v1 is no longer supported. Send the '
            . '"X-AUTH-VERSION: 2" header and sign the request using the v2 scheme.'
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null; // never reached – authenticate() always throws
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        // Returning null would let the request fall through to the access listener
        // and produce the generic 401, so surface the message explicitly.
        throw new HttpException(Response::HTTP_UNAUTHORIZED, $exception->getMessageKey());
    }
}
