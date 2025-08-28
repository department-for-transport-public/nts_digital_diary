<?php

namespace App\EventSubscriber\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Ghost\GovUkFrontendBundle\Model\NotificationBanner;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

class UserLoginSubscriber implements EventSubscriberInterface
{
    public function __construct(protected EntityManagerInterface $entityManager, protected RouterInterface $router) {}

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLoginSuccess',
        ];
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();
        if (!$user instanceof User) {
            return;
        }

        if ($user->getHasLoggedIn()) {
            return;
        }

        $currentState = $user->getHasLoggedIn();
        $user->setHasLoggedIn(true);
        $this->entityManager->flush();

        if ($currentState === null) {
            // Existing user who may or may not have previously logged in
            $notification = new NotificationBanner(
                'user-preferences.new-feature-notification-banner.title',
                'user-preferences.new-feature-notification-banner.heading',
                'user-preferences.new-feature-notification-banner.content',
                [], [], 'messages'
            );
            $event->getRequest()->getSession()->getFlashBag()->add(NotificationBanner::FLASH_BAG_TYPE, $notification);
        }
        $event->setResponse(new RedirectResponse($this->router->generate('app_user_preferences_index')));
    }
}