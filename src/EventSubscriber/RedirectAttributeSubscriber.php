<?php

namespace App\EventSubscriber;

use App\Attribute\Redirect;
use App\Exception\RedirectResponseException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolverInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\ExpressionLanguage;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

class RedirectAttributeSubscriber implements EventSubscriberInterface
{
    public function __construct(
        #[Autowire(service: 'security.expression_language')]
        protected ExpressionLanguage                   $language,
        #[Autowire(service: 'security.authentication.trust_resolver')]
        protected AuthenticationTrustResolverInterface $trustResolver,
        protected RoleHierarchyInterface               $roleHierarchy,
        protected TokenStorageInterface                $tokenStorage,
        protected AuthorizationCheckerInterface        $authChecker,
        protected LoggerInterface                      $logger,
        protected RouterInterface                      $router
    ) {}

    public function handleRedirectAttributes(ControllerArgumentsEvent $event): void
    {
        /** @var array<Redirect> $redirects */
        $redirects = $event->getAttributes(Redirect::class);

        foreach ($redirects as $redirect) {
            if ($this->language->evaluate($redirect->getExpression(), $this->getVariables($event))) {
                $url = $this->router->generate($redirect->getRoute(), $redirect->getRouteParams());
                throw new RedirectResponseException(new RedirectResponse($url));
            }
        }
    }

    public function handleRedirectException(ExceptionEvent $event): void
    {
        $throwable = $event->getThrowable();
        if ($throwable instanceof RedirectResponseException) {
            $event->setResponse($throwable->getRedirectResponse());
            $event->stopPropagation();
        }
    }

    // code should be sync with Symfony\Component\Security\Core\Authorization\Voter\ExpressionVoter
    private function getVariables(ControllerArgumentsEvent $event): array
    {
        $request = $event->getRequest();
        $token = $this->tokenStorage->getToken();

        return [
            'token' => $token,
            'user' => $token?->getUser(),
            'object' => $request,
            'subject' => $request,
            'request' => $request,
            'roles' => $token ?
                $this->roleHierarchy->getReachableRoleNames($token->getRoleNames()) :
                [],
            'trust_resolver' => $this->trustResolver,
            // needed for the is_granted expression function
            'auth_checker' => $this->authChecker,
            'args' => $event->getNamedArguments(),
        ];
    }

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER_ARGUMENTS => [
                ['handleRedirectAttributes', 25], // IsGrantedAttributeListener runs at 20, and we need to get in before that
            ],
            'kernel.exception' => [
                ['handleRedirectException', 0],
            ],
        ];
    }
}
