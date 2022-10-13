<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220819103530 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add journey sharing relation';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE journey ADD shared_from_id VARCHAR(26) DEFAULT NULL');
        $this->addSql('ALTER TABLE journey ADD CONSTRAINT FK_C816C6A25919D5BC FOREIGN KEY (shared_from_id) REFERENCES journey (id)');
        $this->addSql('CREATE INDEX IDX_C816C6A25919D5BC ON journey (shared_from_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE journey DROP FOREIGN KEY FK_C816C6A25919D5BC');
        $this->addSql('DROP INDEX IDX_C816C6A25919D5BC ON journey');
        $this->addSql('ALTER TABLE journey DROP shared_from_id');
    }
}
