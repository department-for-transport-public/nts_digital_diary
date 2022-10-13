<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220817114527 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add is_journey_sharing_enabled flag';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE household ADD is_journey_sharing_enabled TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE household DROP is_journey_sharing_enabled');
    }
}
