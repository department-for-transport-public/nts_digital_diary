<?php


namespace App\Security\HmacAuth;


use App\Entity\ApiUser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

class HmacAuthenticatorV2 extends AbstractHmacAuthenticator
{
    protected const int AUTH_VERSION = 2;

    public function supports(Request $request): ?bool
    {
        return
            $request->headers->has('X-AUTH-KEY')
            && $request->headers->has('X-AUTH-SIGNATURE')
            && static::AUTH_VERSION === (int) $request->headers->get('X-AUTH-VERSION')
            && $request->query->has('timestamp');
    }

    protected function checkSignature(ApiUser $user, array $credentials): void
    {
        /** @var Request $request */
        $request = $credentials['request'];
        // check signature
        $secret = $this->secretGenerator->getSecretForApiUser($user);
        $bodyHash = hash('sha256', $request->getContent());
        $derivedSig = base64_encode(hash_hmac(
            'sha256',
            "{$request->getMethod()}.{$bodyHash}.{$request->getSchemeAndHttpHost()}{$request->getRequestUri()}",
            $secret,
            true
        ));
        if (!hash_equals($derivedSig, $credentials['signature'])) {
            throw new BadCredentialsException('Bad credentials');
        }
    }
}