<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230302131010 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Unique index on household (address, household, area)';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE UNIQUE INDEX UNIQ_54C32FC0ADC99AEBBF9EDA4179F73F02 ON household (address_number, household_number, area_period_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_54C32FC0ADC99AEBBF9EDA4179F73F02 ON household');
    }
}
