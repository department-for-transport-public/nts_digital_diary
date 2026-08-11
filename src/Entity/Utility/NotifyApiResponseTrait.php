<?php

namespace App\Entity\Utility;

use Doctrine\Common\Collections\Collection;

trait NotifyApiResponseTrait
{
    public function getNotifyApiResponses(): Collection
    {
        return $this->apiResponses;
    }

    public function getNotifyApiResponsesMatching(string $eventName, string $notificationClass, bool $sorted = false, bool $reverse = false): array
    {
        $responses = $this->apiResponses
            ->filter(fn(NotifyApiResponse $r) =>
                $r->getEventName() === $eventName &&
                $r->getMessageClass() === $notificationClass
            )
            ->toArray();

        if ($sorted) {
            usort($responses,
                fn(NotifyApiResponse $a, NotifyApiResponse $b) => $reverse ?
                    $a->getTimestamp() <=> $b->getTimestamp() :
                    $b->getTimestamp() <=> $a->getTimestamp()
            );
        }

        return $responses;
    }

    public function addNotifyApiResponse(NotifyApiResponse $apiResponse): static
    {
        if (!$this->apiResponses->contains($apiResponse)) {
            $this->apiResponses[] = $apiResponse;
        }

        return $this;
    }

    public function removeNotifyApiResponse(NotifyApiResponse $apiResponse): static
    {
        $this->apiResponses->removeElement($apiResponse);
        return $this;
    }
}