<?php

namespace App\EventSubscriber;

use App\Event\CompleteOnboardingEvent;
use App\Utility\Security\AccountCreationHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CompleteOnboardingSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AccountCreationHelper $accountCreationHelper,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            CompleteOnboardingEvent::class => 'onboardingComplete'
        ];
    }

    public function onboardingComplete(CompleteOnboardingEvent $event)
    {
        $household = $event->getHousehold();
        $household->setIsOnboardingComplete(true);
        $this->entityManager->flush();

        foreach ($household->getDiaryKeepers() as $diaryKeeper) {
            if ($diaryKeeper->hasIdentifierForLogin()) {
                $this->accountCreationHelper->sendAccountCreationEmail($diaryKeeper);
            }
        }
    }
}