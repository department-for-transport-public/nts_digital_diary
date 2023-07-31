<?php

namespace App\Controller\FormWizard;

use App\Event\FormWizardFormDataEvent;
use App\FormWizard\FormWizardManager;
use App\FormWizard\FormWizardStateInterface;
use App\FormWizard\Place;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Workflow\Transition;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

abstract class AbstractFormWizardController extends AbstractController
{
    protected FormWizardManager $formWizardManager;
    private EventDispatcherInterface $eventDispatcher;

    abstract protected function getState(): FormWizardStateInterface;
    abstract protected function setState(FormWizardStateInterface $state);
    abstract protected function cleanUp();

    abstract protected function getRedirectResponse(?Place $place): RedirectResponse;
    abstract protected function getCancelRedirectResponse(): ?RedirectResponse;

    public function __construct(FormWizardManager $formWizardManager)
    {
        $this->formWizardManager = $formWizardManager;
    }

    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher): void
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @throws Exception
     * @throws ExceptionInterface
     */
    protected function doWorkflow(Request $request, $place = null, array|\Closure $additionalViewData = []): Response
    {
        $formWizardState = $this->getState();
        $initialPlace = new Place($this->formWizardManager->getInitialPlace($formWizardState));
        if (is_null($place)) {
            return $this->getRedirectResponseWithHash($initialPlace);
        }

        if (!$place instanceof Place) {
            $place = new Place($place);
        }

        if ($place->isSameAs($initialPlace) && !$this->formWizardManager->isValidStartPlace($formWizardState, $place)) {
            throw new AccessDeniedHttpException('Invalid start place');
        }

        if (!$place->isSameAs($formWizardState->getPlace())) {
            if ($formWizardState->isValidHistoryPlace($place)
                || $place->isSameAs($initialPlace)
                || $this->formWizardManager->isValidAlternativeStartPlace($formWizardState, $place)
            ) {
                $formWizardState->setPlace($place);
            } else {
                // possibly combination of back link and history back
                // send them to the last allowable history || initial place
                return $this->getRedirectResponseWithHash($formWizardState->getPreviousHistoryPlace() ?? $initialPlace);
            }
        }

        /** @var Form | FormInterface $form */
        $cancelRedirectResponse = $this->getCancelRedirectResponse();
        $form = $this->formWizardManager->createForm($formWizardState, $cancelRedirectResponse?->getTargetUrl());
        $form->handleRequest($request);
        if ($this->eventDispatcher ?? false) {
            $this->eventDispatcher->dispatch(new FormWizardFormDataEvent($form->getData()), FormWizardFormDataEvent::NAME);
        }

        // validate/state-transition
        if (!is_null($button = $form->getClickedButton())) {
            if ($button->getName() === 'cancel') {
                $cancelRedirect = $this->getCancelRedirectResponse();
                if ($cancelRedirect) return $cancelRedirect;
            }
            if ($form->isValid()) {
                if ($transition = $this->formWizardManager->getSingleTransition($formWizardState))
                {
                    return $this->applyTransitionAndRedirect($formWizardState, $transition);
                }
            }
        }

        $stateMetadata = $this->formWizardManager->getStateMetadata($formWizardState);

        if ($additionalViewData instanceof \Closure) {
            $additionalViewData = $additionalViewData($formWizardState);

            if (!is_array($additionalViewData)) {
                throw new \RuntimeException('$additionalViewData closure must return an array');
            }
        }

        return $this->render($stateMetadata['template'], array_merge($additionalViewData, $stateMetadata['view_data'], [
            'form' => $form->createView(),
            'subject' => $formWizardState->getSubject(),
            'formWizardState' => $formWizardState,
        ]));
    }

    protected function applyTransitionAndRedirect(FormWizardStateInterface $formWizardState, Transition $transition): RedirectResponse
    {
        if ($redirectUrl = $this->formWizardManager->applyAndProcessTransition($formWizardState, $transition)) {
            $this->cleanUp();
            return $this->redirect($redirectUrl);
        }

        $this->setState($formWizardState);

        return $this->getRedirectResponseWithHash($formWizardState->getPlace());
    }

    /**
     * Fetches the redirect response and adds an empty hash fragment to the URL if not already present
     * (This is done to avoid skip-link's #main-content from sticking)
     */
    public function getRedirectResponseWithHash(?Place $place): RedirectResponse
    {
        $response = $this->getRedirectResponse($place);
        $url = $response->getTargetUrl();

        if (!str_contains($url, '#')) {
            $response->setTargetUrl($url . '#');
        }

        return $response;
    }
}
