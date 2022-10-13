<?php

namespace App\EventSubscriber;

use App\Features;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Security;

class FormValidationLoggerSubscriber implements EventSubscriberInterface
{
    private Security $security;
    private LoggerInterface $logger;
    private RequestStack $requestStack;

    public function __construct(Security $security, RequestStack $requestStack, LoggerInterface $formValidationLogger)
    {
        $this->security = $security;
        $this->logger = $formValidationLogger;
        $this->requestStack = $requestStack;
    }

    public static function getSubscribedEvents(): array
    {
        // Validation occurs at the default priority, so if we hook in to a lower priority, errors should be available
        return [
            FormEvents::POST_SUBMIT => [
                ['onPostSubmit', -10],
            ]
        ];
    }

    public function onPostSubmit(FormEvent $event)
    {
        $form = $event->getForm();

        // Feature enabled, and (if the parent is null) we have the root form
        if (Features::isEnabled(Features::FORM_ERROR_LOG) && $form->getParent() === null) {
            $this->logErrors($form);
        }
    }

    protected function logErrors(FormInterface $form)
    {
        if ($form->getErrors(true)->count() > 0) {
            $logData = [
                'user' => $this->security->getUser() ? $this->security->getUser()->getUsername() : null,
                'form' => $form->getName(),
                'path' => $this->requestStack->getCurrentRequest()->getPathInfo(),
                'errors' => [],
            ];

            foreach ($form->getErrors(true) as $error) {
                $origin = $error->getOrigin();
                if (!isset($logData['errors'][$origin->getName()])) $logData['errors'][$origin->getName()] = [];
                $logData['errors'][$error->getOrigin()->getName()][] = [
                    'data' => $origin->getViewData(),
                    'message' => $error->getMessage(),
                ];
            }

            $this->logger->debug(json_encode($logData));
        }
    }
}