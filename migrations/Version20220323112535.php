<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220323112535 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'convert stage distance to 2 decimal places';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE stage CHANGE distance_travelled_value distance_travelled_value NUMERIC(8, 2) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE stage CHANGE distance_travelled_value distance_travelled_value NUMERIC(8, 1) DEFAULT NULL');
    }
}
