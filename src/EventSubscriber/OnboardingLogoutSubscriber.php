<?php

namespace App\EventSubscriber;

use App\Entity\InterviewerTrainingRecord;
use App\Security\OneTimePassword\InMemoryOtpUser;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class OnboardingLogoutSubscriber implements EventSubscriberInterface
{
    private Security $security;
    private RouterInterface $router;

    public function __construct(Security $security, RouterInterface $router)
    {
        $this->security = $security;
        $this->router = $router;
    }

    /**
     * When logging out from Onboarding, we want to redirect to the
     * training module if the user was onboarding as part of training
     */
    public function onLogout(LogoutEvent $event) {
        if ($this->security->isGranted(InMemoryOtpUser::ROLE_ON_BOARDING_TRAINING)) {
            $event->setResponse(new RedirectResponse(
                $this->router->generate(
                    'interviewer_training_module_index', [
                        'moduleName' => InterviewerTrainingRecord::MODULE_ONBOARDING
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