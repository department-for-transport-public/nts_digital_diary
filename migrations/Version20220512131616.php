<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220512131616 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add index to property_change_log';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX property_change_idx ON property_change_log (entity_id, entity_class)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX property_change_idx ON property_change_log');
    }
}
