<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220131123712 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add password field to user';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD password VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP password');
    }
}
