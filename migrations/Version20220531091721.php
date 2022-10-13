<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220531091721 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update session table to use a MEDIUMBLOB';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `sessions` MODIFY sess_data MEDIUMBLOB NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `sessions` MODIFY sess_data BLOB NOT NULL');
    }
}
