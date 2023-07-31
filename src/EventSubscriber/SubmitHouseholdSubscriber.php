<?php

namespace App\EventSubscriber;

use App\Event\SubmitHouseholdEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Security;

class SubmitHouseholdSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly Security $security,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            SubmitHouseholdEvent::class => 'submitHousehold',
        ];
    }

    public function submitHousehold(SubmitHouseholdEvent $event)
    {
        $event->getHousehold()->setSubmittedAt(new \DateTime());
        $event->getHousehold()->setSubmittedBy($this->security->getUser()->getUserIdentifier());
        $this->entityManager->flush();
    }
}