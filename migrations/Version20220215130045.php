<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220215130045 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add plain_password field';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD plain_password VARCHAR(1) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP plain_password');
    }
}
