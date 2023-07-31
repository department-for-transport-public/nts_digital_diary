<?php

namespace App\Doctrine\DBAL\Schema;

use App\Doctrine\DBAL\Schema\MySQL\CustomSchemaManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\DefaultSchemaManagerFactory;
use Doctrine\DBAL\Schema\SchemaManagerFactory;

class CustomSchemaManagerFactory implements SchemaManagerFactory
{
    private readonly SchemaManagerFactory $defaultFactory;

    public function __construct()
    {
        $this->defaultFactory = new DefaultSchemaManagerFactory();
    }

    /**
     * @throws Exception
     */
    public function createSchemaManager(Connection $connection): AbstractSchemaManager
    {
        $platform = $connection->getDatabasePlatform();
        if ($platform instanceof AbstractMySQLPlatform) {
            return new CustomSchemaManager($connection, $platform);
        }

        return $this->defaultFactory->createSchemaManager($connection);
    }
}