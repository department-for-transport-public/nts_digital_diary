<?php

namespace App\Messenger\AlphagovNotify;

interface ApiResponseInterface
{
    public function getNotifyApiResponses(): ?array;
    public function getNotifyApiResponse(string $eventName, string $notificationClass): ?array;
    public function addNotifyApiResponse(string $eventName, string $notificationClass, array $notifyApiResponse): ?Object;
}