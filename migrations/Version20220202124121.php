<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220202124121 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add "password reset code" to user';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD password_reset_code VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP password_reset_code');
    }
}
