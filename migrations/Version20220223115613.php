<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220223115613 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add purpose_form_test_group to Household';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE household ADD purpose_form_test_group VARCHAR(1) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE household DROP purpose_form_test_group');
    }
}
