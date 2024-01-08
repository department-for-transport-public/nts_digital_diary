<?php

namespace App\Utility\Metrics\Events;

use App\Entity\DiaryKeeper;

class AdminAddDiaryKeeperEvent extends AbstractEvent
{
    public function __construct(DiaryKeeper $diaryKeeper)
    {
        $this->setDiarySerialFromDiaryKeeper($diaryKeeper);
    }

    public function getName(): string
    {
        return 'Admin: add diary keeper';
    }
}