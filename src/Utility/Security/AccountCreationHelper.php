<?php

namespace App\Utility\Security;

use App\Entity\DiaryKeeper;
use App\Entity\UserPersonInterface;
use App\Messenger\AlphagovNotify\Email;
use App\Utility\AlphagovNotify\Reference;
use App\Utility\Security\UrlSigner;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class AccountCreationHelper
{
    protected MessageBusInterface $messageBus;
    protected RouterInterface $router;
    protected UrlSigner $urlSigner;

    public function __construct(MessageBusInterface $messageBus, RouterInterface $router, UrlSigner $urlSigner)
    {
        $this->messageBus = $messageBus;
        $this->router = $router;
        $this->urlSigner = $urlSigner;
    }

    public function sendAccountCreationEmail(UserPersonInterface $userPerson, string $emailOverride = null): void
    {
        if ($userPerson instanceof DiaryKeeper && $userPerson->getHousehold()->getAreaPeriod()->getTrainingInterviewer()) {
            // we don't want to send emails if it's a diary keeper that's been added as part of training!
            return;
        }

        $routeParameters = [
            'userId' => $userPerson->getUser()->getId(),
        ];

        if ($emailOverride) {
            $routeParameters['email'] = $emailOverride;
        }

        $url = $this->router->generate('auth_account_setup',
            $routeParameters,
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $signedUrl = $this->urlSigner->sign($url, Reference::ACCOUNT_CREATION_LINK_EXPIRY);

        $this->messageBus->dispatch(new Email(
            Reference::ACCOUNT_CREATION_EVENT,
            get_class($userPerson),
            $userPerson->getId(),
            $emailOverride ?? $userPerson->getUser()->getUserIdentifier(),
            $this->getAccountCreateTemplateIdForUserPerson($userPerson),
            ['name' => $userPerson->getName(), 'url' => $signedUrl],
        ));
    }

    protected function getAccountCreateTemplateIdForUserPerson(UserPersonInterface $userPerson): string
    {
        if ($userPerson->getUser()->hasRole('ROLE_DIARY_KEEPER')) {
            return Reference::ACCOUNT_CREATION_DIARY_KEEPER_EMAIL_TEMPLATE_ID;
        } else if ($userPerson->getUser()->hasRole('ROLE_INTERVIEWER')) {
            return Reference::ACCOUNT_CREATION_INTERVIEWER_EMAIL_TEMPLATE_ID;
        }
        throw new \RuntimeException("unexpected user type in account creation");
    }
}