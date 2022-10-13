<?php


namespace App\Utility\ConfirmAction;


use Ghost\GovUkFrontendBundle\Model\NotificationBanner;

interface ConfirmActionInterface
{
    public function getSubject();
    public function getTranslationKeyPrefix(): string;
    public function getTranslationDomain(): ?string;
    public function getTranslationParameters(): array;
    public function getFormOptions(): array;
    public function doConfirmedAction($formData);
    public function getConfirmedBanner(): NotificationBanner;
    public function getCancelledBanner(): NotificationBanner;
    public function getExtraViewData(): array;
}
