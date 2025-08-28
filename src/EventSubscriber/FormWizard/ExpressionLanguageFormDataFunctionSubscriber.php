<?php

namespace App\EventSubscriber\FormWizard;

use Ghost\GovUkCoreBundle\Event\FormWizard\FormDataEvent;
use Ghost\GovUkCoreBundle\ExpressionLanguage\WizardFormDataFunctionProvider;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ExpressionLanguageFormDataFunctionSubscriber implements EventSubscriberInterface
{
    public function __construct(protected WizardFormDataFunctionProvider $wizardFormDataFunctionProvider)
    {}

    public function handleFormDataEvent(FormDataEvent $event): void
    {
        $this->wizardFormDataFunctionProvider->setFormData($event->getFormData());
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FormDataEvent::NAME => 'handleFormDataEvent',
        ];
    }
}
