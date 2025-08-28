<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250729094205 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Migrate feedback_message column from varchar(255) -> text';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE feedback_message CHANGE page page LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE feedback_message CHANGE page page VARCHAR(255) DEFAULT NULL');
    }
}
