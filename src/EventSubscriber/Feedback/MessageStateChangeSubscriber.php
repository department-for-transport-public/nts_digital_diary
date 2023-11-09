<?php

namespace App\EventSubscriber\Feedback;

use App\Entity\Feedback\Message;
use App\Entity\Feedback\Note;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\CompletedEvent;
use Twig\Environment;

class MessageStateChangeSubscriber implements EventSubscriberInterface
{
    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected Environment $twigEnvironment,
    ) {}

    public function transitionCompleted(CompletedEvent $event): void
    {
        /** @var Message $subject */
        $subject = $event->getSubject();

        $noteText = $this->twigEnvironment->render('feedback/state-transition-note.txt.twig', [
            'fromState' => $event->getTransition()->getFroms()[0],
            'toState' => $event->getTransition()->getTos()[0],
            'context' => $event->getContext(),
        ]);

        $subject->addNote($newNote = (new Note())
            ->setNote($noteText)
        );
        $this->entityManager->persist($newNote);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.feedback_message.completed' => 'transitionCompleted',
        ];
    }
}