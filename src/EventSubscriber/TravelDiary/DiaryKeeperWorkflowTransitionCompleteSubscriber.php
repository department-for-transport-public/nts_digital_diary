<?php

namespace App\EventSubscriber\TravelDiary;

use App\Entity\AreaPeriod;
use App\Entity\DiaryKeeper;
use App\Messenger\AlphagovNotify\Email;
use App\Utility\AlphagovNotify\Reference;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Workflow\Event\Event;

class DiaryKeeperWorkflowTransitionCompleteSubscriber implements EventSubscriberInterface
{
    private MessageBusInterface $messageBus;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    public function onComplete(Event $event)
    {
        /** @var DiaryKeeper $diaryKeeper */
        $diaryKeeper = $event->getSubject();

        $this->notifyDiaryKeeper($diaryKeeper);
        $this->notifyInterviewers($diaryKeeper->getHousehold()->getAreaPeriod());
    }

    protected function notifyDiaryKeeper(DiaryKeeper $diaryKeeper): void
    {
        if ($diaryKeeper->getUser()->hasIdentifierForLogin())
        // email the DK to acknowledge diary completion.
        $this->messageBus->dispatch(new Email(
            Reference::DIARY_COMPLETE_ACKNOWLEDGEMENT['eventName'],
            DiaryKeeper::class,
            $diaryKeeper->getId(),
            $diaryKeeper->getUser()->getUserIdentifier(),
            Reference::DIARY_COMPLETE_ACKNOWLEDGEMENT['templateId'],
            ['name' => $diaryKeeper->getName()]
        ));
    }

    protected function notifyInterviewers(AreaPeriod $areaPeriod): void
    {
        // email the interviewer to inform of diary completion (and household state?)
        // which interviewer - all of them?!
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.travel_diary_state.transition.' . DiaryKeeper::TRANSITION_COMPLETE => 'onComplete',
        ];
    }
}