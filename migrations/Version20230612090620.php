<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230612090620 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Method: Create unique index on (description_translation_key, type)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE UNIQUE INDEX description_translation_key_type_unique ON method (description_translation_key, type)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX description_translation_key_type_unique ON method');
    }
}
