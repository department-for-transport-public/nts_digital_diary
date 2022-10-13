<?php

namespace App\Controller;

use App\Exception\RedirectResponseException;
use Ghost\GovUkFrontendBundle\Model\NotificationBanner;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as SymfonyAbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Translation\TranslatableMessage;

class AbstractController extends SymfonyAbstractController
{
    protected function addSuccessBanner(string $translationPrefix, string $translationDomain): void
    {
        $this->addBanner(NotificationBanner::STYLE_SUCCESS, $translationPrefix, $translationDomain);
    }

    protected function addBanner(string $style, string $translationPrefix, string $translationDomain): void
    {
        $banner = new NotificationBanner(
            new TranslatableMessage('notification.success'),
            "{$translationPrefix}.success-banner.heading",
            "{$translationPrefix}.success-banner.content",
            ['style' => $style],
            [],
            $translationDomain
        );

        $this->addFlash(NotificationBanner::FLASH_BAG_TYPE, $banner);
    }

    protected function redirectToDashboardIfAppropriate(): void
    {
        switch (true) {
            case $this->isGranted('ROLE_INTERVIEWER'):
                throw new RedirectResponseException(new RedirectResponse($this->generateUrl('interviewer_dashboard')));

            case $this->isGranted('ROLE_DIARY_KEEPER'):
                throw new RedirectResponseException(new RedirectResponse($this->generateUrl('traveldiary_dashboard')));
        }
    }
}