<?php

namespace App\Utility\Security;

use App\Entity\DiaryKeeper;
use App\Entity\Interviewer;
use App\Entity\UserPersonInterface;
use App\Messenger\AlphagovNotify\Email;
use App\Utility\AlphagovNotify\Reference;
use App\Utility\DateTimeFormats;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class AccountCreationHelper
{
    public const string EMAIL_OVERRIDE_PARAM = 'email';

    public function __construct(
        protected MessageBusInterface $messageBus,
        protected RouterInterface $router,
        protected UrlSigner $urlSigner,
        protected DateTimeFormats $dateTimeFormats,
    ) {}

    public function sendAccountCreationEmail(UserPersonInterface $userPerson, string $emailOverride = null): bool
    {
        if ($userPerson instanceof DiaryKeeper && $userPerson->getHousehold()->isTraining()) {
            // we don't want to send emails if it's a diary keeper that's been added as part of training!
            return false;
        }

        if (!$userPerson->getUser()->hasIdentifierForLogin()) {
            // we don't want to send emails if the user doesn't have a valid login
            // this could happen if they click an expired link after their email address has been purged
            return false;
        }

        $signedUrl = $this->generateAccountCreationUrl(
            $userPerson,
            $emailOverride,
            linkExpiryOverride: $this->calculateLinkExpiry($userPerson),
            extraParams: [UrlSigner::SOURCE_TRACKING_PARAMETER_NAME => Reference::ACCOUNT_CREATION_EVENT]
        );

        $additionalParams = $userPerson instanceof DiaryKeeper
            ? [
                'startDate' => $userPerson->getHousehold()->getDiaryWeekStartDate()->format($this->dateTimeFormats->getDate('with-dow')),
                'diaryKeeperSerial' => $userPerson->getSerialNumber(),
            ] : [];

        $this->messageBus->dispatch(new Email(
            Reference::ACCOUNT_CREATION_EVENT,
            $userPerson::class,
            $userPerson->getId(),
            $emailOverride ?? $userPerson->getUser()->getUserIdentifier(),
            $this->getAccountCreateTemplateIdForUserPerson($userPerson),
            array_merge($additionalParams, [
                'name' => $userPerson->getName(),
                'url' => $signedUrl,
            ]),
        ));

        return true;
    }

    public function generateAccountCreationUrl(UserPersonInterface $userPerson, ?string $emailOverride = null, ?int $currentTime = null, ?int $linkExpiryOverride = null, array $extraParams = []): string
    {
        $routeParameters = [
            'userId' => $userPerson->getUser()->getId(),
        ];

        if ($emailOverride) {
            $routeParameters[self::EMAIL_OVERRIDE_PARAM] = $emailOverride;
        }

        $url = $this->router->generate('auth_account_setup',
            array_merge($extraParams, $routeParameters),
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        return $this->urlSigner->sign($url, $linkExpiryOverride ?? Reference::ACCOUNT_CREATION_LINK_EXPIRY, $currentTime);
    }

    /**
     * Diary keepers receive their link before the diary week starts, so a fixed validity measured
     * from send time can lapse mid-week. Anchor the expiry to the end of the diary week (plus a
     * completion buffer) instead, with a floor so links sent late (or without a week date) stay usable.
     */
    public function calculateLinkExpiry(UserPersonInterface $userPerson, ?int $currentTime = null): int
    {
        $currentTime ??= time();

        if ($userPerson instanceof DiaryKeeper && ($weekStart = $userPerson->getHousehold()?->getDiaryWeekStartDate())) {
            $weekEnd = (clone $weekStart)->modify('+7 days')->getTimestamp() + Reference::ACCOUNT_CREATION_LINK_GRACE;

            return max($weekEnd - $currentTime, Reference::ACCOUNT_CREATION_LINK_MIN_EXPIRY);
        }

        return Reference::ACCOUNT_CREATION_LINK_EXPIRY;
    }

    protected function getAccountCreateTemplateIdForUserPerson(UserPersonInterface $userPerson): string
    {
        if ($userPerson instanceof DiaryKeeper) {
            return Reference::ACCOUNT_CREATION_DIARY_KEEPER_EMAIL_TEMPLATE_ID;
        } else if ($userPerson instanceof Interviewer) {
            return Reference::ACCOUNT_CREATION_INTERVIEWER_EMAIL_TEMPLATE_ID;
        }
        throw new \RuntimeException("unexpected user type in account creation");
    }
}