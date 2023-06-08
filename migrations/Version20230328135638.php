<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230328135638 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'User: Add emailPurgeDate';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD email_purge_date DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP email_purge_date');
    }
}
