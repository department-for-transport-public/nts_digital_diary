<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220221095411 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add indexes for unique constraints on diary_keeper';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE UNIQUE INDEX UNIQ_3083496A5E237E06E79FF843 ON diary_keeper (name, household_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_3083496A96901F54E79FF843 ON diary_keeper (number, household_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_3083496A5E237E06E79FF843 ON diary_keeper');
        $this->addSql('DROP INDEX UNIQ_3083496A96901F54E79FF843 ON diary_keeper');
    }
}
