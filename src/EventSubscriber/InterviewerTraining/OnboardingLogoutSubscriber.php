<?php

namespace App\EventSubscriber\InterviewerTraining;

use App\Entity\InterviewerTrainingRecord;
use App\Security\OneTimePassword\InMemoryOtpUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class OnboardingLogoutSubscriber implements EventSubscriberInterface
{
    public function __construct(
        protected readonly Security        $security,
        protected readonly RouterInterface $router,
    ) {}

    /**
     * When logging out from Onboarding, we want to redirect to the
     * training module if the user was onboarding as part of training
     */
    public function onLogout(LogoutEvent $event): void
    {
        if ($this->security->isGranted(InMemoryOtpUser::ROLE_MANUAL_ON_BOARDING_TRAINING)) {
            $event->setResponse(new RedirectResponse(
                $this->router->generate(
                    'interviewer_training_module_index', [
                        'moduleName' => InterviewerTrainingRecord::MODULE_MANUAL_ONBOARDING_PRACTICE
                    ]
                )
            ));
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LogoutEvent::class => 'onLogout',
        ];
    }
}