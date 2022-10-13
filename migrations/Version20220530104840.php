<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220530104840 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove erroneous propertyChangeLog entries (createdBy / modifiedBy)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("DELETE FROM property_change_log WHERE property_name = 'createdAt' OR property_name = 'createdBy' OR property_name = 'modifiedAt' OR property_name = 'modifiedBy'");
    }

    public function down(Schema $schema): void
    {
    }
}
