<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220907115316 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add consent flag to users';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD has_consented TINYINT(1) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP has_consented');
    }
}
