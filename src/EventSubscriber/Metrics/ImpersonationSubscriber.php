<?php

namespace App\EventSubscriber\Metrics;

use App\Entity\User;
use App\Utility\Metrics\Events\ImpersonationEvent;
use App\Utility\Metrics\MetricsHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Http\Event\SwitchUserEvent;

class ImpersonationSubscriber implements EventSubscriberInterface
{
    public function __construct(protected MetricsHelper $metrics) {}

    public static function getSubscribedEvents(): array
    {
        return [
            SwitchUserEvent::class => 'onSwitchUser',
        ];
    }

    public function onSwitchUser(SwitchUserEvent $event): void
    {
        /** @var User $targetUser */
        $targetUser = $event->getTargetUser();
        $token = $event->getToken();

        /** @var ?User $originalUser */
        $originalUser = $token instanceof SwitchUserToken ? $token->getOriginalToken()->getUser() : null;

        if ($targetUser->getDiaryKeeper() && $originalUser->getInterviewer()) {
            $this->metrics->log(new ImpersonationEvent($targetUser->getDiaryKeeper()));
        }
    }
}