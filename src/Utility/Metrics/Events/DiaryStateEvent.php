<?php

namespace App\Utility\Metrics\Events;

use App\Entity\DiaryKeeper;

class DiaryStateEvent extends AbstractEvent
{
    public function __construct(DiaryKeeper $diaryKeeper, string $fromState)
    {
        $this->metadata['from'] = $fromState;
        $this->metadata['to'] = $diaryKeeper->getDiaryState();
        $this->setDiarySerialFromDiaryKeeper($diaryKeeper);
    }

    public function getName(): string
    {
        return 'Diary: state change';
    }
}