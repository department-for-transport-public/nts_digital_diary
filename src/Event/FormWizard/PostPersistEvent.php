<?php

namespace App\Event\FormWizard;

use Symfony\Contracts\EventDispatcher\Event;

class PostPersistEvent extends Event
{
    public function __construct(protected mixed $subject, protected ?string $sourceWizardController) {}

    public function getSubject(): mixed
    {
        return $this->subject;
    }

    public function getSourceWizardController(): ?string
    {
        return $this->sourceWizardController;
    }
}