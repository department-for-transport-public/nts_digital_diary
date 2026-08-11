<?php

namespace App\Utility\TravelDiary;

use App\Entity\DiaryKeeper;
use App\Messenger\AlphagovNotify\Email;
use App\Utility\AlphagovNotify\Reference;
use App\Utility\DateTimeFormats;
use App\Utility\DiaryKeeperFeaturesService;
use App\Utility\Security\AccountCreationHelper;
use App\Utility\Security\UrlSigner;
use DateTimeImmutable;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\RouterInterface;

class RemindersHelper
{
    public function __construct(
        protected MessageBusInterface $messageBus,
        protected RouterInterface $router,
        protected UrlSigner $urlSigner,
        protected AccountCreationHelper $accountCreationHelper,
        protected DateTimeFormats $dateTimeFormats,
        protected DiaryKeeperFeaturesService $diaryKeeperFeaturesService,
    ) {}

    public function sendDiaryWeekReminderEmail(DiaryKeeper $diaryKeeper): void
    {
        if (
            !$diaryKeeper->getHousehold()->getIsOnboardingComplete()
            || $diaryKeeper->getHousehold()->isTraining()
            || !$diaryKeeper->getUser()->hasIdentifierForLogin()
            || $diaryKeeper->getUser()->getHasPendingUsernameChange()
            || $diaryKeeper->getWeekStartReminderSentAt() !== null
            || $diaryKeeper->getIdCreatedAt() >= new \DateTime('-2 days')
        ) {
            return;
        }

        $signedUrl = $this->accountCreationHelper->generateAccountCreationUrl(
            $diaryKeeper,
            linkExpiryOverride: $this->accountCreationHelper->calculateLinkExpiry($diaryKeeper),
            extraParams: [UrlSigner::SOURCE_TRACKING_PARAMETER_NAME => Reference::REMIND_DIARY_KEEPER_EVENT],
        );

        $this->messageBus->dispatch(new Email(
            Reference::REMIND_DIARY_KEEPER_EVENT,
            $diaryKeeper::class,
            $diaryKeeper->getId(),
            $diaryKeeper->getUser()->getUserIdentifier(),
            Reference::REMIND_DIARY_KEEPER_EMAIL_TEMPLATE_ID,
            [
                'name' => $diaryKeeper->getName(),
                'url' => $signedUrl,
                'startDate' => $diaryKeeper->getHousehold()->getDiaryWeekStartDate()->format($this->dateTimeFormats->getDate('with-dow')),
                'diaryKeeperSerial' => $diaryKeeper->getSerialNumber(),
            ],
        ));
        $diaryKeeper->setWeekStartReminderSentAt(new DateTimeImmutable());
    }

}