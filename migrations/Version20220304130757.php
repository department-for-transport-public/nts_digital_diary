<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220304130757 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add property_change_log table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE property_change_log (id VARCHAR(26) NOT NULL, entity_id VARCHAR(26) NOT NULL, entity_class VARCHAR(255) NOT NULL, property_name VARCHAR(255) NOT NULL, property_value LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\', is_interviewer TINYINT(1) NOT NULL, timestamp DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetimemicrosecond)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE property_change_log');
    }
}
