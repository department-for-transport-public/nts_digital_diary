<?php

namespace App\Utility\Metrics\Events;

use App\Entity\DiaryKeeper;
use App\Utility\Metrics\MetricsHelper;

class ImpersonationEvent extends AbstractEvent
{
    public function __construct(DiaryKeeper $targetDiaryKeeper)
    {
        $this->setDiarySerialFromDiaryKeeper($targetDiaryKeeper);
    }

    public function getName(): string
    {
        return 'Interviewer: impersonate';
    }
}