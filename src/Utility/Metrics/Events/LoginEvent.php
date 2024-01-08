<?php

namespace App\Utility\Metrics\Events;

class LoginEvent extends AbstractEvent
{
    public function __construct(
        protected ?string $diarySerial,
        string $firewallName,
    ) {
        $this->metadata['firewall'] = $firewallName;
    }

    public function getName(): string
    {
        return 'Login: success';
    }
}