<?php

namespace App\Messenger\AlphagovNotify;

use App\Entity\Utility\NotifyApiResponse;
use Doctrine\Common\Collections\Collection;

interface ApiResponseInterface
{
    public function getNotifyApiResponses(): ?Collection;
    public function getNotifyApiResponsesMatching(string $eventName, string $notificationClass): ?array;
    public function addNotifyApiResponse(NotifyApiResponse $notifyApiResponse): static;
}