<?php


namespace App\Security\GoogleIap;


use App\Features;
use Exception;
use Google\Auth\AccessToken;
use Google\Cloud\Core\Compute\Metadata;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\CustomCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;

class IapAuthenticator extends AbstractAuthenticator
{
    const EXTRA_FIELD_USER_ID = 'IAPid';
    private bool $isGaeEnvironment;

    public function __construct(protected AdminRoleResolver $roleResolver)
    {
        $this->isGaeEnvironment = Features::isEnabled(Features::GAE_ENVIRONMENT);
    }

    protected function getUserEmail(Request $request): string
    {
        return $request->headers->get('X-Goog-Authenticated-User-Email', 'no-email');
    }

    protected function getJwtAssertion(Request $request): ?string
    {
        return $request->headers->get('X-Goog-Iap-Jwt-Assertion', null);
    }

    protected function getUserId(Request $request): string
    {
        return $request->headers->get('X-Goog-Authenticated-User-Id', 'no-id');
    }

    public function supports(Request $request): ?bool
    {
        return $request->headers->has('X-Goog-Iap-Jwt-Assertion');
    }

    public function authenticate(Request $request): Passport
    {
        $emailAddress = $this->getUserEmail($request);

        $credentials = [
            'emailAddress' => $emailAddress,
            'userId' => $this->getUserId($request),
            'assertion' => $this->getJwtAssertion($request),
        ];

        return new Passport(
            new UserBadge('', fn() => new IapUser(
                $emailAddress,
                $credentials['assertion'],
                $this->roleResolver->getRolesForEmailAddress($emailAddress),
            )),
            new CustomCredentials(
                [$this, 'customAuthenticator'],
                $credentials
            ),
        );
    }

    public function customAuthenticator($credentials, UserInterface $user): bool
    {
        if ($user->getPassword() === false) {
            return false;
        }

        if (!$this->isGaeEnvironment) {
            return true;
        }

        /** @var IapUser $user */
        try {
            $metadata = new Metadata();
            $audience = "/projects/{$metadata->getNumericProjectId()}/apps/{$metadata->getProjectId()}";
            $assertionId = $this->validateAssertion($user->getPassword(), $audience);
            return $assertionId === $credentials['userId'];
        } catch (Exception $e) {
            return false;
        }
    }

    private function validateAssertion(string $idToken, string $audience): string
    {
        $auth = new AccessToken();
        $info = $auth->verify($idToken, [
            'certsLocation' => AccessToken::IAP_CERT_URL,
            'throwException' => true,
        ]);

        if ($info === false) {
            throw new Exception('Google token verification failed');
        }

        if ($audience != $info['aud'] ?? '') {
            throw new Exception(sprintf(
                'Audience %s did not match expected %s', $info['aud'], $audience
            ));
        }

        if (empty($info['email']) || empty($info['sub'])) {
            throw new Exception('Google token verification does not contain email/sub.');
        }

        // The email address returned is the plain email address (without namespace)
        return $info['sub'];
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return null;
    }
}