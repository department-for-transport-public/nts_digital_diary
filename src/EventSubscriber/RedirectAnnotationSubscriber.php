<?php

namespace App\EventSubscriber;

use App\Annotation\Redirect;
use App\Exception\RedirectResponseException;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Request\ArgumentNameConverter;
use Sensio\Bundle\FrameworkExtraBundle\Security\ExpressionLanguage;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolverInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

class RedirectAnnotationSubscriber implements EventSubscriberInterface
{
    private ExpressionLanguage $language;
    private AuthenticationTrustResolverInterface $trustResolver;
    private RoleHierarchyInterface $roleHierarchy;
    private TokenStorageInterface $tokenStorage;
    private AuthorizationCheckerInterface $authChecker;
    private LoggerInterface $logger;
    private ArgumentNameConverter $argumentNameConverter;
    private RouterInterface $router;

    public function __construct(ArgumentNameConverter $argumentNameConverter, ExpressionLanguage $language, AuthenticationTrustResolverInterface $trustResolver, RoleHierarchyInterface $roleHierarchy, TokenStorageInterface $tokenStorage, AuthorizationCheckerInterface $authChecker, LoggerInterface $logger, RouterInterface $router)
    {
        $this->language = $language;
        $this->trustResolver = $trustResolver;
        $this->roleHierarchy = $roleHierarchy;
        $this->tokenStorage = $tokenStorage;
        $this->authChecker = $authChecker;
        $this->logger = $logger;
        $this->argumentNameConverter = $argumentNameConverter;
        $this->router = $router;
    }

    /**
     * @throws RedirectResponseException
     */
    public function handleRedirectAnnotations(ControllerArgumentsEvent $event)
    {
        $request = $event->getRequest();
        if (!$configurations = $request->attributes->get('_redirect')) {
            return;
        }
        $this->runComponentChecks();

        foreach ($configurations as $configuration) {
            /** @var Redirect $configuration */
            if ($this->language->evaluate($configuration->getExpression(), $this->getVariables($event))) {
                throw new RedirectResponseException(new RedirectResponse(
                    $this->router->generate($configuration->getRoute(),
                    $configuration->getRouteParams())
                ));
            }
        }
    }

    public function handleRedirectException(ExceptionEvent $event)
    {
        $throwable = $event->getThrowable();
        if ($throwable instanceof RedirectResponseException) {
            $event->setResponse($throwable->getRedirectResponse());
        }
    }

    // code should be sync with Symfony\Component\Security\Core\Authorization\Voter\ExpressionVoter
    private function getVariables(ControllerArgumentsEvent $event): array
    {
        $request = $event->getRequest();
        $token = $this->tokenStorage->getToken();
        $variables = [
            'token' => $token,
            'user' => $token ? $token->getUser() : null,
            'object' => $request,
            'subject' => $request,
            'request' => $request,
            'roles' => $token ? $this->roleHierarchy->getReachableRoleNames($token->getRoleNames()) : [],
            'trust_resolver' => $this->trustResolver,
            // needed for the is_granted expression function
            'auth_checker' => $this->authChecker,
        ];

        $controllerArguments = $this->argumentNameConverter->getControllerArguments($event);

        if ($diff = array_intersect(array_keys($variables), array_keys($controllerArguments))) {
            foreach ($diff as $key => $variableName) {
                if ($variables[$variableName] === $controllerArguments[$variableName]) {
                    unset($diff[$key]);
                }
            }

            if ($diff) {
                $singular = 1 === \count($diff);
                $this->logger->warning(sprintf('Controller argument%s "%s" collided with the built-in security expression variables. The built-in value%s are being used for the @Redirect expression.', $singular ? '' : 's', implode('", "', $diff), $singular ? 's' : ''));
            }
        }

        // controller variables should also be accessible
        return array_merge($controllerArguments, $variables);
    }

    protected function runComponentChecks()
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'kernel.controller_arguments' => [
                ['handleRedirectAnnotations', 1],
            ],
            'kernel.exception' => [
                ['handleRedirectException', 0],
            ],
        ];
    }
}