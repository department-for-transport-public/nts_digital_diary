<?php


namespace App\Security\HmacAuth;


use App\Entity\ApiUser;
use Exception;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
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

abstract class AbstractHmacAuthenticator extends AbstractAuthenticator
{
    protected const int SIGNATURE_VALID_FOR = 30;
    protected const int SIGNATURE_EARLY = 5;

    public function __construct(
        protected readonly SecretGenerator        $secretGenerator,
        #[Autowire(service: 'hmac_authenticator_cache')]
        protected readonly CacheItemPoolInterface $cache,
    ) {}

    protected function getSignature(Request $request): ?string
    {
        return $request->headers->get('X-AUTH-SIGNATURE');
    }

    public function authenticate(Request $request): Passport
    {
        $key = $request->headers->get('X-AUTH-KEY');
        $signature = $this->getSignature($request);
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
                $this->customAuthenticator(...),
                [
                    'key' => $key,
                    'signature' => $signature,
                    'timestamp' => $request->query->get('timestamp', 1),
                    'queryString' => $request->server->get('QUERY_STRING'),
                    'request' => $request,
                ]
            ),
        );
    }

    /**
     * @param ApiUser $user
     * @throws InvalidArgumentException
     */
    public function customAuthenticator(array $credentials, UserInterface $user): bool
    {
        $this->checkTimestamp($credentials['timestamp']);

        // check cache (base64 isn't valid for cache keys)
        $cacheSig = bin2hex(base64_decode($credentials['signature']));
        $cacheItem = $this->cache->getItem("signature-{$user->getId()}-{$cacheSig}");
        if ($cacheItem->isHit()) {
            throw new CredentialsExpiredException('A a request with this signature has been seen before');
        }

        $this->checkSignature($user, $credentials);

        $this->cache->save($cacheItem
            ->expiresAfter(self::SIGNATURE_VALID_FOR + self::SIGNATURE_EARLY)
            ->set(true)
        );

        return true;
    }

    public function checkTimestamp($timestamp): void
    {
        $now = time();
        if (($now - $timestamp) > self::SIGNATURE_VALID_FOR) {
            throw new CredentialsExpiredException('Timestamp is too old');
        }
        if (($timestamp - $now) > self::SIGNATURE_EARLY) {
            throw new CredentialsExpiredException('Timestamp is in the future');
        }
    }

    abstract protected function checkSignature(ApiUser $user, array $credentials): void;

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
            switch ($exception->getPrevious()::class) {
                case CredentialsExpiredException::class :
                    throw $exception->getPrevious();
            }
        }

        if ($exception instanceof TooManyLoginAttemptsAuthenticationException) {
            throw new TooManyRequestsHttpException(null, strtr($exception->getMessageKey(), $exception->getMessageData()));
        }

        return null;
    }
}