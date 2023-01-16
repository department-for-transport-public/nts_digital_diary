<?php

namespace App\EventSubscriber\ApiPlatform;

use App\Entity\Interviewer;
use App\Utility\AccountCreationHelper;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;

class InterviewerPersistenceSubscriber implements EventSubscriberInterface
{
    private AccountCreationHelper $accountCreationHelper;
    private string $appEnvironment;

    public function __construct(AccountCreationHelper $accountCreationHelper, string $appEnvironment)
    {
        $this->accountCreationHelper = $accountCreationHelper;
        $this->appEnvironment = $appEnvironment;
    }

    public function postPersist(LifecycleEventArgs $args)
    {
        $object = $args->getObject();
        if ($object instanceof Interviewer && !in_array($this->appEnvironment, ['test'])) {
            $this->accountCreationHelper->sendAccountCreationEmail($object);
        }
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::postPersist
        ];
    }
}