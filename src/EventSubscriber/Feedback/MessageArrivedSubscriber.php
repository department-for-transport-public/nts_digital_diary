<?php

namespace App\EventSubscriber\Feedback;

use App\Entity\Feedback\CategoryEnum;
use App\Entity\Feedback\Message;
use App\Messenger\Feedback\AssignFeedbackMessage;
use App\Security\GoogleIap\AdminRoleResolver;
use App\Utility\Feedback\NotifyHelper;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsDoctrineListener(event: Events::postPersist)]
class MessageArrivedSubscriber
{
    public function __construct(
        protected AdminRoleResolver $adminRoleResolver,
        protected NotifyHelper $notifyHelper,
    ) {}

    public function postPersist(PostPersistEventArgs $args): void
    {
        $object = $args->getObject();

        if (!$object instanceof Message) {
            return;
        }

        foreach($this->adminRoleResolver->getAssigners() as $assigner) {
            foreach($assigner->getNotificationEmails() as $notificationEmail) {
                $this->notifyHelper->sendFeedbackArrivedMessage($notificationEmail, $object);
            }
        }
    }
}
