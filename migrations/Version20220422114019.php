<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220422114019 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Diary keeper: Remove is_proxied flag';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE diary_keeper DROP is_proxied');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE diary_keeper ADD is_proxied TINYINT(1) NOT NULL');
    }
}
