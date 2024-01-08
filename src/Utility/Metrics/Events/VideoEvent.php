<?php

namespace App\Utility\Metrics\Events;

class VideoEvent extends AbstractEvent
{
    public function __construct(protected string $type, string $videoId, string $urlPath) {
        $this->metadata['video_id'] = $videoId;
        $this->metadata['url_path'] = $urlPath;
    }

    public function getName(): string
    {
        return "Video: {$this->type}";
    }
}