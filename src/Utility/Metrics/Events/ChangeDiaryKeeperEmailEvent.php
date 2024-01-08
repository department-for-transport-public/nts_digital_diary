<?php

namespace App\Utility\Metrics\Events;

use App\Entity\DiaryKeeper;
use App\Entity\Interviewer;

class ChangeDiaryKeeperEmailEvent extends AbstractEvent
{
    public function __construct(DiaryKeeper $diaryKeeper, Interviewer $interviewer)
    {
        $this->setDiarySerialFromDiaryKeeper($diaryKeeper);
    }

    public function getName(): string
    {
        return 'Interviewer: change diary-keeper email';
    }
}