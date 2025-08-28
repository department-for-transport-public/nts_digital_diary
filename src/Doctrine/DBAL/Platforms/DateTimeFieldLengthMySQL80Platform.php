<?php

namespace App\Doctrine\DBAL\Platforms;

use Doctrine\DBAL\Platforms\MySQL80Platform;

class DateTimeFieldLengthMySQL80Platform extends MySQL80Platform
{
    public function getDateTimeTypeDeclarationSQL(array $column): string
    {
        $length = $column['length'] ?? null;
        if (isset($column['version']) && $column['version'] === true) {
            return $length
                ? "TIMESTAMP($length)"
                : 'TIMESTAMP';
        }
        return $length
            ? "DATETIME($length)"
            : 'DATETIME';
    }
}