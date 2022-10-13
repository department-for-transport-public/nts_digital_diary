<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220908100821 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add checkLetter to household';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE household ADD check_letter VARCHAR(1) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE household DROP check_letter');
    }
}
