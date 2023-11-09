<?php

namespace App\Entity\Feedback;

class Group
{
    public function __construct(
        protected string $name,
        protected string $domain,
        protected array  $notificationEmails,
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function getDomain(): string
    {
        return $this->domain;
    }

    public function getNotificationEmails(): array
    {
        return $this->notificationEmails;
    }
}