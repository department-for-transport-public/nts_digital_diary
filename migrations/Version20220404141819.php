<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220404141819 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE area_period DROP start_date');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_A333EF4CD7943D68 ON area_period (area)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_A333EF4CD7943D68 ON area_period');
        $this->addSql('ALTER TABLE area_period ADD start_date DATE NOT NULL');
    }
}
