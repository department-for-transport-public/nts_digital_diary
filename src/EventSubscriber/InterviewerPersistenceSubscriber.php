<?php

namespace App\EventSubscriber;

use App\Entity\Interviewer;
use App\Utility\Security\AccountCreationHelper;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;

#[AsDoctrineListener(event: Events::postPersist)]
class InterviewerPersistenceSubscriber
{
    public function __construct(
        protected readonly AccountCreationHelper $accountCreationHelper,
        protected readonly string                $appEnvironment,
    ) {}

    public function postPersist(LifecycleEventArgs $args): void
    {
        $object = $args->getObject();
        if ($object instanceof Interviewer && $this->appEnvironment !== 'test') {
            $this->accountCreationHelper->sendAccountCreationEmail($object);
        }
    }
}
