<?php

namespace App\EventSubscriber\Feedback;

use App\Entity\Feedback\Note;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\Security\Core\Security;

class NotePersistSubscriber implements EventSubscriberInterface
{
    public function __construct(
        protected Security $security,
    ) {}

    public function prePersist(PrePersistEventArgs $eventArgs): void
    {
        $object = $eventArgs->getObject();

        if (!$object instanceof Note) {
            return;
        }

        $object
            ->setCreatedAt(new DateTime())
            ->setUserIdentifier($this->security->getToken()?->getUserIdentifier() ?? 'system')
        ;
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::prePersist,
        ];
    }
}