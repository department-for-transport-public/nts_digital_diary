<?php

namespace App\EventSubscriber\Security;

use App\Entity\AreaPeriod;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Http\Event\SwitchUserEvent;

class ImpersonationSourceParameterSubscriber implements EventSubscriberInterface
{
    protected ?string $previousSource = null;

    public function __construct(
        protected Security $security,
    ) {}

    public function onSwitchUser(SwitchUserEvent $event): void
    {
        $user = $event->getTargetUser();
        if ($user instanceof User) {
            if (! $event->getToken() instanceof SwitchUserToken) {
                // exiting impersonation
                $this->previousSource = $event->getRequest()->getSession()->get('switch_user_source');
                $event->getRequest()->getSession()->remove('switch_user_source');
                return;
            }

            if ($diaryKeeper = $user->getDiaryKeeper()) {
                if ($diaryKeeper?->getHousehold()->getAreaPeriod()->getArea() === AreaPeriod::TRAINING_PERSONAL_DIARY_AREA_SERIAL
                    && $this->previousSource
                ) {
                    // switching from one diary keeper to another. This happens as two events... exit switch to
                    // the original impersonator user, then switch to the new user. We use `previousSource` to
                    // remember the source from the first event in the second.
                    $event->getRequest()->getSession()->set('switch_user_source', $this->previousSource);
                    $event->getRequest()->attributes->set('switch_user_start', $this->previousSource);
                    return;
                }
            }
        }
        // source is not set, and we're not exiting impersonation... set the source.
        $source = $event->getRequest()->server->get('HTTP_REFERER');
        $event->getRequest()->getSession()->set('switch_user_source', $source);
        $event->getRequest()->attributes->set('switch_user_start', $source);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SwitchUserEvent::class => 'onSwitchUser',
        ];
    }
}