<?php

namespace App\Doctrine\DBAL\Middleware;

use App\Doctrine\DBAL\Driver\DateTimeFieldLengthMySQL80Driver;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Middleware;

class DateTimeFieldLengthMiddleware implements Middleware
{
    public function wrap(Driver $driver): Driver
    {
        return new DateTimeFieldLengthMySQL80Driver($driver);
    }
}