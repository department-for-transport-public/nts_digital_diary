<?php

namespace App\EventSubscriber\Onboarding;

use App\Entity\Vehicle;
use App\Event\CompleteOnboardingEvent;
use App\FormWizard\OnBoarding\DirectOnboardingState;
use App\Security\OneTimePassword\InMemoryOtpUser;
use App\Security\Voter\Onboarding\DirectOnboardingTrainingVoter;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\EnteredEvent;

class CompleteDirectOnboardingSubscriber implements EventSubscriberInterface
{
    public function __construct(
        protected EventDispatcherInterface $eventDispatcher,
        protected Security                 $security,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.form_wizard.on_boarding.direct_onboarding.entered.' . DirectOnboardingState::STATE_FINISH => 'completeDirectOnboarding',
        ];
    }

    public function completeDirectOnboarding(EnteredEvent $event): void
    {
        /** @var DirectOnboardingState $state */
        $state = $event->getSubject();
        $user = $this->security->getUser();

        $this->eventDispatcher->dispatch(new CompleteOnboardingEvent($state->getSubject(), OnboardingType::DIRECT));

        // If it's direct onboarding training, save the household to the user (after resolving proxies) so the summary can be displayed.
        if ($user instanceof InMemoryOtpUser && $this->security->isGranted(DirectOnboardingTrainingVoter::DIRECT_ONBOARDING_TRAINING)) {
            $state->getSubject()->getVehicles()->map(function (Vehicle $v): \App\Entity\Vehicle {
                $x = $v->getMethod()->getDescriptionTranslationKey();
                return $v;
            });
            $user->setHousehold($state->getSubject());
        }
    }
}