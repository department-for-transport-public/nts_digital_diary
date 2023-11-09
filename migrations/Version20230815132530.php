<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230815132530 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Allow deleting of a source shared journey';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE journey DROP FOREIGN KEY FK_C816C6A25919D5BC');
        $this->addSql('ALTER TABLE journey ADD CONSTRAINT FK_C816C6A25919D5BC FOREIGN KEY (shared_from_id) REFERENCES journey (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE journey DROP FOREIGN KEY FK_C816C6A25919D5BC');
        $this->addSql('ALTER TABLE journey ADD CONSTRAINT FK_C816C6A25919D5BC FOREIGN KEY (shared_from_id) REFERENCES journey (id)');
    }
}
