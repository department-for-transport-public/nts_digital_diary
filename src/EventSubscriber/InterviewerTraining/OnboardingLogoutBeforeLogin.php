<?php

namespace App\EventSubscriber\InterviewerTraining;

use App\Entity\OtpUser;
use App\Exception\RedirectResponseException;
use App\Security\OneTimePassword\TrainingUserProvider;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class OnboardingLogoutBeforeLogin implements EventSubscriberInterface
{
    public function __construct(
        protected TokenStorageInterface $tokenStorage
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onRequest', 0],
        ];
    }

    public function onRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if ($request->getMethod() === Request::METHOD_GET
            && $request->query->has(TrainingUserProvider::INTERVIEWER_ID_QUERY_PARAM)
            && $this->tokenStorage->getToken()?->getUser() instanceof OtpUser
        ) {
            $this->tokenStorage->setToken();
            throw new RedirectResponseException(new RedirectResponse($request->getRequestUri()));
        }
    }
}