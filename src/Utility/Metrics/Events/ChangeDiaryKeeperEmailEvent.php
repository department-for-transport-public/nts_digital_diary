<?php

namespace App\Utility\Metrics\Events;

use App\Entity\DiaryKeeper;

class ChangeDiaryKeeperEmailEvent extends AbstractEvent
{
    public function __construct(DiaryKeeper $diaryKeeper)
    {
        $this->setDiarySerialFromDiaryKeeper($diaryKeeper);
    }

    public function getName(): string
    {
        return 'Interviewer: change diary-keeper email';
    }
}