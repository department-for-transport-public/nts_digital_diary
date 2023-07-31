<?php

namespace App\EventSubscriber\FormWizard;

use App\FormWizard\TravelDiary\SplitJourneyState;
use Ghost\GovUkFrontendBundle\Model\NotificationBanner;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Workflow\Event\EnteredEvent;

class SplitJourneySubscriber implements EventSubscriberInterface
{
    public function __construct(protected RequestStack $requestStack)
    {}

    public function onEntered(EnteredEvent $event): void
    {
        $state = $event->getSubject();

        if (!$state instanceof SplitJourneyState) {
            return;
        }

        $subject = $state->getSubject();

        $sourceDayNumber = $subject->getSourceJourney()->getDiaryDay()->getNumber();
        $returnDayNumber = $subject->getDestinationJourney()->getDiaryDay()->getNumber();

        $request = $this->requestStack->getCurrentRequest();
        $flashBag = $request->getSession()->getFlashBag();

        if ($returnDayNumber > $sourceDayNumber) {
            // If the return journey crossed the day boundary, display an alternate banner that gives more information
            $prefix = 'split-journey.day-boundary-notification';
        } else {
            $prefix = 'split-journey.success-notification';
        }

        $flashBag->add(NotificationBanner::FLASH_BAG_TYPE, new NotificationBanner(
            new TranslatableMessage('notification.success', [], 'messages'),
            "{$prefix}.heading",
            "{$prefix}.content",
            ['style' => NotificationBanner::STYLE_SUCCESS],
            [
                'sourceDayNumber' => $sourceDayNumber,
                'returnDayNumber' => $returnDayNumber,
            ],
            'travel-diary'
        ));
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.form_wizard.travel_diary.split_journey.entered.finish' => 'onEntered',
        ];
    }
}