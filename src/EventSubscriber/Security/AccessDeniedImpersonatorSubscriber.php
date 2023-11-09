<?php

namespace App\EventSubscriber\Security;

use App\Security\ImpersonatorAuthorizationChecker;
use ReflectionException;
use ReflectionMethod;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Http\Firewall;
use Symfony\Component\Security\Http\Firewall\SwitchUserListener;

class AccessDeniedImpersonatorSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
        private readonly ImpersonatorAuthorizationChecker $impersonatorAuthorizationChecker,
        private readonly SwitchUserListener $switchUserListener,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    /**
     * @throws ReflectionException
     */
    public function onKernelException(ExceptionEvent $event)
    {
        $request = $event->getRequest();

        if (
            !$event->getThrowable() instanceof AccessDeniedHttpException
            || !$this->tokenStorage->getToken() instanceof SwitchUserToken
            || !$this->impersonatorAuthorizationChecker->isGranted('ROLE_INTERVIEWER')
            || $request->getUri() !== $request->getSession()->get('switch_user_source')
        ) {
            return;
        }

        $reflectionMethod = new ReflectionMethod($this->switchUserListener, 'attemptExitUser');
        $reflectionMethod->setAccessible(true);
        $token = $reflectionMethod->invoke($this->switchUserListener, $event->getRequest());
        $this->tokenStorage->setToken($token);

        $event->setResponse(new RedirectResponse($request->getUri()));
    }
}