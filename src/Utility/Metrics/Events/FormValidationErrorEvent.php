<?php

namespace App\Utility\Metrics\Events;

use App\Entity\DiaryKeeper;

class FormValidationErrorEvent extends AbstractEvent
{
    public function __construct(DiaryKeeper $diaryKeeper, string $formName, string $path, array $errors)
    {
        $this->metadata['form'] = $formName;
        $this->metadata['path'] = $path;
        $this->metadata['errors'] = $errors;
        $this->setDiarySerialFromDiaryKeeper($diaryKeeper);
    }

    public function getName(): string
    {
        return 'Form validation error';
    }
}