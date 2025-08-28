<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240805121303 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove unused journey->is_partial column';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE journey DROP is_partial');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE journey ADD is_partial TINYINT(1) NOT NULL');
    }
}
