<?php

namespace App\Utility\Metrics\Events;

use App\Entity\DiaryKeeper;
use App\Utility\Metrics\MetricsHelper;

abstract class AbstractEvent implements EventInterface
{
    protected array $metadata = [];
    protected ?string $diarySerial;

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function getDiarySerial(): ?string
    {
        return $this->diarySerial ?? null;
    }

    public function setDiarySerialFromDiaryKeeper(DiaryKeeper $diaryKeeper): void
    {
        $this->diarySerial = $diaryKeeper->getSerialNumber(...MetricsHelper::GET_SERIAL_METHOD_ARGS);
    }
}