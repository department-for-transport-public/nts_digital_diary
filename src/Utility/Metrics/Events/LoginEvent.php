<?php

namespace App\Utility\Metrics\Events;

class LoginEvent extends AbstractEvent
{
    public function __construct(
        protected ?string $diarySerial,
        string $firewallName,
        ?string $source = null
    ) {
        $this->metadata['firewall'] = $firewallName;
        if ($source) {
            $this->metadata['source'] = $source;
        }
    }

    public function getName(): string
    {
        return 'Login: success';
    }
}