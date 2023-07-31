<?php

namespace App\FormWizard;

use App\Utility\PropertyAccessHelper;
use Doctrine\ORM\EntityManagerInterface;
use Ghost\GovUkFrontendBundle\Form\Type\ButtonGroupType;
use Ghost\GovUkFrontendBundle\Form\Type\ButtonType;
use Ghost\GovUkFrontendBundle\Form\Type\LinkType;
use Ghost\GovUkFrontendBundle\Model\NotificationBanner;
use RuntimeException;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Workflow\EventListener\ExpressionLanguage;
use Symfony\Component\Workflow\Registry;
use Symfony\Component\Workflow\Transition;
use Symfony\Component\Workflow\WorkflowInterface;

class FormWizardManager
{
    private FormFactoryInterface $formFactory;
    private RouterInterface $router;
    private EntityManagerInterface $entityManager;
    private RequestStack $requestStack;
    private Registry $registry;
    private ExpressionLanguage $expressionLanguage;
    private AuthorizationCheckerInterface $authChecker;

    public function __construct(Registry $registry, ExpressionLanguage $expressionLanguage, FormFactoryInterface $formFactory, RouterInterface $router, EntityManagerInterface $entityManager, RequestStack $requestStack, AuthorizationCheckerInterface $authChecker)
    {
        $this->formFactory = $formFactory;
        $this->router = $router;
        $this->entityManager = $entityManager;
        $this->requestStack = $requestStack;
        $this->registry = $registry;
        $this->expressionLanguage = $expressionLanguage;
        $this->authChecker = $authChecker;
    }

    public function getWorkflow(FormWizardStateInterface $state): ?WorkflowInterface
    {
        return $this->registry->get($state);
    }

    public function getInitialPlace(FormWizardStateInterface $state): string
    {
        $initialPlaces = $this->getWorkflow($state)->getDefinition()->getInitialPlaces();
        return $initialPlaces[array_key_last($initialPlaces)];
    }

    public function getStateMetadata(FormWizardStateInterface $state, ?string $alternativePlace = null): array
    {
        $placeMetadata = array_merge([
            'form_class' => null,
            'form_options' => [],
            'view_data' => [],
            'template' => null,
            'is_valid_start_place' => true,
            'is_valid_alternative_start_place' => false,
            'form_data_property' => 'subject',
            'translation_parameters' => [],
        ], $this->getWorkflow($state)->getMetadataStore()->getPlaceMetadata($alternativePlace ?? $state->getPlace()));
        $placeMetadata['translation_parameters'] = PropertyAccessHelper::resolveMap($state, $placeMetadata['translation_parameters']);
        $placeMetadata['view_data'] = PropertyAccessHelper::resolveMap($state, $placeMetadata['view_data']);
        $placeMetadata['view_data']['translation_parameters'] = $placeMetadata['translation_parameters'];

        $subject = $state->getSubject();
        $isEditing = $subject && method_exists($subject, 'getId') && $subject->getId();

        $placeMetadata['view_data']['translation_parameters']['activity'] = $isEditing ? 'editing' : 'adding';
        return $placeMetadata;
    }

    protected function getFormData(FormWizardStateInterface $state, ?array $stateMetadata)
    {
        $formDataProperty = $stateMetadata['form_data_property'];
        switch (true) {
            case $formDataProperty === null :
                return $state;
            case $formDataProperty === false :
                return null;
            default :
                return (PropertyAccess::createPropertyAccessor())->getValue($state, $formDataProperty);
        }
    }

    public function getSingleTransition(FormWizardStateInterface $state): ?Transition
    {
        $transitions = $this->getWorkflow($state)->getEnabledTransitions($state);

        // If we have only one possible transition, and it is meant to persist/flush
        // then we want to have a better label for the "continue" button
        switch (count($transitions)) {
            case 1:
                return $transitions[array_key_last($transitions)];
            case 0:
                throw new RuntimeException("[FormWizard] No transitions on {$this->requestStack->getCurrentRequest()->getPathInfo()} ({$state->getPlace()})");
            default:
                $transitionNames = join(", ", array_map(function(Transition $a){return $a->getName();}, $transitions));
                throw new RuntimeException("[FormWizard] Zero or many transitions on {$this->requestStack->getCurrentRequest()->getPathInfo()}: $transitionNames");
        }
    }

    public function getTransitionMetadata(WorkflowInterface $stateMachine, Transition $transition): array
    {
        $metadata = $stateMachine->getMetadataStore()->getTransitionMetadata($transition);
        $isSavePoint = ($metadata['persist'] ?? false) === true;

        return array_merge([
            'submit_label' => ($isSavePoint ? 'actions.save-and-continue' : 'actions.continue'),
            'cancel_label' => 'actions.cancel',
            'persist' => false,
            'notification_banner' => null,
            'redirect_route' => null,
            'context' => [],
        ], $metadata);
    }

    public function createForm(FormWizardStateInterface $state, $cancelLinkHref = null): FormInterface
    {
        $stateMetadata = $this->getStateMetadata($state);
        $transitionMetadata = $this->getTransitionMetadata($this->getWorkflow($state), $this->getSingleTransition($state));

        $form = $stateMetadata['form_class']
            ? $this->formFactory->create(
                $stateMetadata['form_class'],
                $this->getFormData($state, $stateMetadata),
                PropertyAccessHelper::resolveMap($state, $stateMetadata['form_options']))
            : $this->formFactory->createBuilder()->getForm();

        $form->add('button_group', ButtonGroupType::class);
        $form->get('button_group')
            ->add('continue', ButtonType::class, [
            'type' => 'submit',
            'label' => $transitionMetadata['submit_label'],
            'translation_domain' => $transitionMetadata['submit_translation_domain'] ?? 'messages',
        ]);
        if ($cancelLinkHref) {
            $form->get('button_group')
                ->add('cancel', LinkType::class, [
                    'href' => $cancelLinkHref,
                    'label' => $transitionMetadata['cancel_label'],
                    'translation_domain' => $transitionMetadata['cancel_translation_domain'] ?? 'messages',
                ]);
        }

        return $form;
    }

    public function applyAndProcessTransition(FormWizardStateInterface $state, Transition $transition): ?string
    {
        $workflow = $this->getWorkflow($state);
        $transitionMetadata = $this->getTransitionMetadata($workflow, $transition);

        $workflow->apply($state, $transition->getName(), PropertyAccessHelper::resolveMap($state, $transitionMetadata['context']));

        if ($transitionMetadata['persist'] ?? false)
        {
            $subject = $state->getSubject();

            if ($subject instanceof MultipleEntityInterface) {
                foreach($subject->getEntitiesToPersist() as $entity) {
                    $this->persistSubject($entity);
                }
            } else {
                $this->persistSubject($subject);
            }
        }

        if ($notificationBanner = $transitionMetadata['notification_banner'] ?? false) {
            $this->handleNotificationBanners($notificationBanner, $state);
        }

        $redirectUrl = null;
        if ($redirectRoute = $transitionMetadata['redirect_route'] ?? false) {
            $redirectUrl = $this->resolveRedirectRouteForTransition($redirectRoute, $state);
        }

        return $redirectUrl;
    }

    public function isValidStartPlace(FormWizardStateInterface $state, ?string $place): bool
    {
        return $this->stateSatisfiesPlaceExpression($state, $place, 'is_valid_start_place');
    }

    public function isValidAlternativeStartPlace(FormWizardStateInterface $state, string $alternativePlace): bool
    {
        return $this->stateSatisfiesPlaceExpression($state, $alternativePlace, 'is_valid_alternative_start_place');
    }

    protected function stateSatisfiesPlaceExpression(FormWizardStateInterface $state, ?string $place, string $expressionKey): bool
    {
        $expression = ($this->getStateMetadata($state, $place))[$expressionKey];

        return is_bool($expression) ?
            $expression :
            $this->expressionLanguage->evaluate($expression, $this->getExpressionLanguageVariables($state));
    }

    protected function getExpressionLanguageVariables(FormWizardStateInterface $state): array
    {
        return [
            'state' => $state,
            'auth_checker' => $this->authChecker,
        ];
    }

    protected function persistSubject($subject): void
    {
        if (!$this->entityManager->contains($subject)) {
            $this->entityManager->persist($subject);
        }
        $this->entityManager->flush();
    }

    protected function handleNotificationBanners(array $notificationBanner, FormWizardStateInterface $state): void
    {
        if (($notificationBanner['title'] ?? false) && ($notificationBanner['heading'] ?? false) && ($notificationBanner['content'] ?? false)) {
            $translationParameters = PropertyAccessHelper::resolveMap($state, $notificationBanner['translation_parameters'] ?? []);
            $this->requestStack->getSession()->getFlashBag()->add(NotificationBanner::FLASH_BAG_TYPE, new NotificationBanner(
                $notificationBanner['title'],
                $notificationBanner['heading'],
                $notificationBanner['content'],
                $notificationBanner['options'] ?? [],
                $translationParameters,
                $notificationBanner['translation_domain'] ?? null
            ));
        }
    }

    protected function resolveRedirectRouteForTransition($redirectRoute, FormWizardStateInterface $state): string
    {
        if (is_array($redirectRoute)) {
            $routeParameters = PropertyAccessHelper::resolveMap($state, $redirectRoute['parameters'] ?? []);
            $hashParameters = PropertyAccessHelper::resolveMap($state, $redirectRoute['hash_parameters'] ?? []);
            $hash = str_replace(array_keys($hashParameters), array_values($hashParameters), $redirectRoute['hash'] ?? '');
            $routeName = $redirectRoute['name'];
        } else {
            $routeParameters = [];
            $hash = false;
            $routeName = $redirectRoute;
        }
        return $this->router->generate($routeName, $routeParameters) . ($hash ? "#$hash" : '');
    }
}