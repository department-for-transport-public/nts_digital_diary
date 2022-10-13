<?php

namespace App\Utility;

use DateTime;

class SessionTimeoutHelper
{
    // format needed by JS when interpreting the session expiry and warning timestamps
    const DATE_FORMAT = 'Y-m-d H:i:s';

    protected int $warningThreshold;

    public function __construct(int $warningThreshold = 300)
    {
        $this->warningThreshold = $warningThreshold;
    }

    public function getExpiryTime(): string
    {
        $maxLifetime = $this->getMaxLifetime();
        return (new DateTime("{$maxLifetime} seconds"))->format(self::DATE_FORMAT);
    }

    public function getWarningTime(): string
    {
        $maxLifetime = $this->getMaxLifetime() - $this->warningThreshold;
        return (new DateTime("{$maxLifetime} seconds"))->format(self::DATE_FORMAT);
    }

    protected function getMaxLifetime(): string
    {
        return ini_get('session.gc_maxlifetime');
    }
}