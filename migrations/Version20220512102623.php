<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220512102623 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'User: Remove plainPassword field persistence';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP plain_password');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD plain_password VARCHAR(1) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
    }
}
