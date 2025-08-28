<?php

namespace App\EventSubscriber\Feedback;

use App\Entity\Feedback\Note;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Events;
use Symfony\Bundle\SecurityBundle\Security;

#[AsDoctrineListener(event: Events::prePersist)]
class NotePersistSubscriber
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
}
