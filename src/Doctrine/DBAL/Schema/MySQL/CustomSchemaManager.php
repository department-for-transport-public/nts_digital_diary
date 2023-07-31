<?php

namespace App\Doctrine\DBAL\Schema\MySQL;

use Doctrine\DBAL\Platforms\MySQL\CollationMetadataProvider\CachingCollationMetadataProvider;
use Doctrine\DBAL\Platforms\MySQL\CollationMetadataProvider\ConnectionCollationMetadataProvider;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\MySQLSchemaManager;

class CustomSchemaManager extends MySQLSchemaManager
{
    public function createComparator(): Comparator
    {
        return new CustomComparator(
            $this->_platform,
            new CachingCollationMetadataProvider(new ConnectionCollationMetadataProvider($this->_conn))
        );
    }
}