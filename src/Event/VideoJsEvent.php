<?php

namespace App\Event;

use Symfony\Contracts\EventDispatcher\Event;

class VideoJsEvent extends Event
{
    public const TYPE_PLAY = 'play';
    public const TYPE_ENDED = 'ended';

    public function __construct(protected string $type, protected string $urlPath, protected string $videoId, protected ?array $additionalData = []) {}

    public function getType(): string
    {
        return $this->type;
    }

    public function getUrlPath(): string
    {
        return $this->urlPath;
    }

    public function getVideoId(): string
    {
        return $this->videoId;
    }

    public function getAdditionalData(): ?array
    {
        return $this->additionalData;
    }
}