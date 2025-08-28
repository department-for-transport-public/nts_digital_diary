<?php


namespace App\EventSubscriber\FormWizard;


use Ghost\GovUkCoreBundle\FormWizard\FormWizardStateInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\Event;

class StateHistorySubscriber implements EventSubscriberInterface
{
    public function onEnter(Event $event): void
    {
        $subject = $event->getSubject();
        if ($subject instanceof FormWizardStateInterface) {
            $subject->addPlaceToHistory($subject->getPlace());
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.enter' => 'onEnter',
        ];
    }
}