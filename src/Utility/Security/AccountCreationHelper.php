<?php

namespace App\Utility\Security;

use App\Entity\DiaryKeeper;
use App\Entity\UserPersonInterface;
use App\Messenger\AlphagovNotify\Email;
use App\Utility\AlphagovNotify\Reference;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class AccountCreationHelper
{
    public const EMAIL_OVERRIDE_PARAM = 'email';

    public function __construct(protected MessageBusInterface $messageBus, protected RouterInterface $router, protected UrlSigner $urlSigner)
    {
    }

    public function sendAccountCreationEmail(UserPersonInterface $userPerson, string $emailOverride = null): void
    {
        if ($userPerson instanceof DiaryKeeper && $userPerson->getHousehold()->getAreaPeriod()->getTrainingInterviewer()) {
            // we don't want to send emails if it's a diary keeper that's been added as part of training!
            return;
        }

        $signedUrl = $this->generateAccountCreationUrl($userPerson, $emailOverride);

        $this->messageBus->dispatch(new Email(
            Reference::ACCOUNT_CREATION_EVENT,
            $userPerson::class,
            $userPerson->getId(),
            $emailOverride ?? $userPerson->getUser()->getUserIdentifier(),
            $this->getAccountCreateTemplateIdForUserPerson($userPerson),
            ['name' => $userPerson->getName(), 'url' => $signedUrl],
        ));
    }

    public function generateAccountCreationUrl(UserPersonInterface $userPerson, ?string $emailOverride = null, ?int $currentTime = null): string
    {
        $routeParameters = [
            'userId' => $userPerson->getUser()->getId(),
        ];

        if ($emailOverride) {
            $routeParameters[self::EMAIL_OVERRIDE_PARAM] = $emailOverride;
        }

        $url = $this->router->generate('auth_account_setup',
            $routeParameters,
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        return $this->urlSigner->sign($url, Reference::ACCOUNT_CREATION_LINK_EXPIRY, $currentTime);
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