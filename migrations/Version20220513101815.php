<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220513101815 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add basic metadata fields to Journey and Stage entities';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE journey ADD created_at DATETIME DEFAULT NULL, ADD created_by VARCHAR(255) DEFAULT NULL, ADD modified_at DATETIME DEFAULT NULL, ADD modified_by VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE stage ADD created_at DATETIME DEFAULT NULL, ADD created_by VARCHAR(255) DEFAULT NULL, ADD modified_at DATETIME DEFAULT NULL, ADD modified_by VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE journey DROP created_at, DROP created_by, DROP modified_at, DROP modified_by');
        $this->addSql('ALTER TABLE stage DROP created_at, DROP created_by, DROP modified_at, DROP modified_by');
    }
}
