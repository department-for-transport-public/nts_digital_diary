<?php

namespace App\EventSubscriber\Metrics;

use App\Entity\User;
use App\Security\OneTimePassword\OtpUserInterface;
use App\Utility\Metrics\Events\LoginEvent;
use App\Utility\Metrics\MetricsHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\RememberMeToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

class LoginSubscriber implements EventSubscriberInterface
{
    public function __construct(protected MetricsHelper $metrics, protected RequestStack $requestStack) {}

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $token = $event->getAuthenticatedToken();
        if ($token instanceof RememberMeToken) {
            // don't log remember me token logins
            return;
        }
        if (($firewallName = $this->getFirewallName($token)) === 'admin') {
            // don't log admin logins, as admin is stateless, and login success event fires on every request!
            return;
        }

        $this->metrics->log(new LoginEvent(
//            $this->getUserSerial($event->getUser(), $token->getUserIdentifier()),
//            true,
            $this->getDiarySerial($event->getUser()),
            $firewallName
        ));
    }

    protected function getFirewallName(TokenInterface $token): ?string
    {
        if (!$token instanceof PostAuthenticationToken
            && !$token instanceof UsernamePasswordToken
        ) {
            return 'unknown';
        }
        return $token->getFirewallName();
    }

    protected function getUserSerial(?UserInterface $user, ?string $fallbackUserIdentifier): ?string
    {
        return match(true) {
            $user instanceof OtpUserInterface => $user->getUserIdentifier(),
            $user instanceof User =>
                $user->getInterviewer()?->getSerialId()
                    ?? $user->getDiaryKeeper()->getSerialNumber(...MetricsHelper::GET_SERIAL_METHOD_ARGS),
            default => $fallbackUserIdentifier,
        };
    }

    protected function getDiarySerial(?UserInterface $user): ?string
    {
        return match(true) {
            $user instanceof OtpUserInterface => $user->getAreaPeriod()?->getArea(),
            default => null,
        };
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLoginSuccess',
        ];
    }
}