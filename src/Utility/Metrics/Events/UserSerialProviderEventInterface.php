<?php

namespace App\Utility\Metrics\Events;

interface UserSerialProviderEventInterface extends EventInterface {
    public function getUserSerial(): string;
}