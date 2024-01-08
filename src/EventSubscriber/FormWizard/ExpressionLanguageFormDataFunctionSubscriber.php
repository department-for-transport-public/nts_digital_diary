<?php


namespace App\EventSubscriber\FormWizard;


use App\Event\FormWizard\FormDataEvent;
use App\ExpressionLanguage\WizardFormDataFunctionProvider;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ExpressionLanguageFormDataFunctionSubscriber implements EventSubscriberInterface
{
    private WizardFormDataFunctionProvider $wizardFormDataFunctionProvider;

    public function __construct(WizardFormDataFunctionProvider $wizardFormDataFunctionProvider)
    {
        $this->wizardFormDataFunctionProvider = $wizardFormDataFunctionProvider;
    }

    public function handleFormDataEvent(FormDataEvent $event)
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