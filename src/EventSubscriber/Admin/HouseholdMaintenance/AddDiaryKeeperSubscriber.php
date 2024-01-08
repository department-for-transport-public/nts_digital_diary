<?php

namespace App\EventSubscriber\Admin\HouseholdMaintenance;

use App\Entity\DiaryKeeper;
use App\Utility\Metrics\Events\AdminAddDiaryKeeperEvent;
use App\Utility\Metrics\MetricsHelper;
use App\Utility\Security\AccountCreationHelper;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Events;

class AddDiaryKeeperSubscriber implements EventSubscriberInterface
{
    public function __construct(
        protected AccountCreationHelper $accountCreationHelper,
        protected MetricsHelper $metrics,
    ) {}

    public function postPersist(PostPersistEventArgs $args): void
    {
        $object = $args->getObject();

        if ($object instanceof DiaryKeeper) {
            $household = $object->getHousehold();

            // A new DiaryKeeper was added, but onboarding is already complete.
            // They must have been added via the admin maintenance panel.
            //
            // Regardless, since emails get sent upon onboarding completion,
            // and onboarding is already complete, we need to make sure an email
            // is sent.
            if ($household?->getIsOnboardingComplete()) {
                if ($object->hasIdentifierForLogin()) {
                    $this->accountCreationHelper->sendAccountCreationEmail($object);
                }

                $this->metrics->log(new AdminAddDiaryKeeperEvent($object));
            }
        }
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::postPersist,
        ];
    }
}