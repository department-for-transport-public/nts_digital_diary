<?php


namespace App\Security\HmacAuth;


use App\Entity\ApiUser;
use Exception;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\CredentialsExpiredException;
use Symfony\Component\Security\Core\Exception\TooManyLoginAttemptsAuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\CustomCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;

class HmacAuthenticator extends AbstractAuthenticator
{
    private SecretGenerator $secretGenerator;
    private CacheItemPoolInterface $cache;

    private const SIGNATURE_VALID_FOR = 30;
    private const SIGNATURE_EARLY = 5;

    public function __construct(SecretGenerator $secretGenerator, CacheItemPoolInterface $hmacAuthenticatorCache)
    {
        $this->secretGenerator = $secretGenerator;
        $this->cache = $hmacAuthenticatorCache;
    }

    public function supports(Request $request): ?bool
    {
        return
            $request->headers->has('X-AUTH-KEY')
            && $request->headers->has('X-AUTH-SIGNATURE')
            && $request->query->has('timestamp')
            ;
    }

    public function authenticate(Request $request): Passport
    {
        $key = $request->headers->get('X-AUTH-KEY');
        $signature = $request->headers->get('X-AUTH-SIGNATURE');
        if (null === $key) {
            throw new AuthenticationCredentialsNotFoundException('No API key provided');
        }
        if (null === $signature) {
            throw new AuthenticationCredentialsNotFoundException('No auth signature provided');
        }

        // validate key, signature, timestamp
        return new Passport(
            new UserBadge($key),
            new CustomCredentials(
                [$this, 'customAuthenticator'],
                [
                    'key' => $key,
                    'signature' => $signature,
                    'timestamp' => $request->query->get('timestamp', 1),
                    'queryString' => $request->server->get('QUERY_STRING'),
                ]
            ),
        );
    }

    /**
     * @param $credentials
     * @param ApiUser $user
     * @return bool
     */
    public function customAuthenticator($credentials, UserInterface $user): bool
    {
        $now = time();

        // check timestamp
        if (($now - $credentials['timestamp']) > self::SIGNATURE_VALID_FOR) {
            throw new CredentialsExpiredException('Timestamp is too old');
        }
        if (($credentials['timestamp'] - $now) > self::SIGNATURE_EARLY) {
            throw new CredentialsExpiredException('Timestamp is in the future');
        }

        // check cache
        $cacheItem = $this->cache->getItem("signature-{$user->getKey()}-{$credentials['signature']}");
        if ($cacheItem->isHit()) {
            throw new CredentialsExpiredException('A a request with this signature has been seen before');
        }

        // check signature
        $secret = $this->secretGenerator->getSecretForApiUser($user);
        $derivedSig = base64_encode(hash_hmac('sha256', $credentials['queryString'], $secret, true));
        if (!hash_equals($derivedSig, $credentials['signature'])) {
            throw new BadCredentialsException('Bad credentials');
        }

        $this->cache->save($cacheItem
            ->expiresAfter(self::SIGNATURE_VALID_FOR + self::SIGNATURE_EARLY)
            ->set(true)
        );

        return true;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function isInteractive(): bool
    {
        return false;
    }

    /**
     * @throws Exception
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        if ($exception->getPrevious()) {
            switch (get_class($exception->getPrevious()))
            {
                case CredentialsExpiredException::class :
                    throw $exception->getPrevious();
            }
        }

        if ($exception instanceof TooManyLoginAttemptsAuthenticationException)
        {
            throw new TooManyRequestsHttpException(null, strtr($exception->getMessageKey(), $exception->getMessageData()));
        }

        return null;
    }
}