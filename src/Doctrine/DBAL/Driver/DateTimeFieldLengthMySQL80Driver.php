<?php

namespace App\Doctrine\DBAL\Driver;

use App\Doctrine\DBAL\Platforms\DateTimeFieldLengthMySQL80Platform;
use Doctrine\DBAL\Driver\Middleware\AbstractDriverMiddleware;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySQL80Platform;
use Doctrine\DBAL\ServerVersionProvider;

class DateTimeFieldLengthMySQL80Driver extends AbstractDriverMiddleware
{
    public function getDatabasePlatform(ServerVersionProvider $versionProvider): AbstractPlatform
    {
        $parentPlatform = parent::getDatabasePlatform($versionProvider);
        if ($parentPlatform instanceof MySQL80Platform) {
            return new DateTimeFieldLengthMySQL80Platform();
        }

        return $parentPlatform;
    }
}