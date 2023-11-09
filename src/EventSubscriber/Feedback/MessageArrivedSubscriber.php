<?php

namespace App\EventSubscriber\Feedback;

use App\Entity\Feedback\CategoryEnum;
use App\Entity\Feedback\Message;
use App\Messenger\Feedback\AssignFeedbackMessage;
use App\Security\GoogleIap\AdminRoleResolver;
use App\Utility\Feedback\NotifyHelper;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Workflow\WorkflowInterface;

class MessageArrivedSubscriber implements EventSubscriberInterface
{
    public function __construct(
        protected AdminRoleResolver $adminRoleResolver,
        protected NotifyHelper $notifyHelper,
        protected MessageBusInterface $messageBus,
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

        if ($object->getCategory() === CategoryEnum::Feedback) {
            $this->messageBus->dispatch(new AssignFeedbackMessage($object->getId(), 'DfT'));
        }
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::postPersist,
        ];
    }
}