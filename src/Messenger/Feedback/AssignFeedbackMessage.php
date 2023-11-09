<?php

namespace App\Messenger\Feedback;

class AssignFeedbackMessage extends AbstractMessage
{
    public function __construct(protected string $feedbackMessageId, protected string $assignTo){}

    public function getFeedbackMessageId(): string
    {
        return $this->feedbackMessageId;
    }

    public function getAssignTo(): string
    {
        return $this->assignTo;
    }
}