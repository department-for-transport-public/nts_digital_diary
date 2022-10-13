<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220915092733 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add year and month columns to AreaPeriod';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE area_period ADD year INT NOT NULL, ADD month INT NOT NULL');
        $this->addSql("UPDATE area_period set year = CAST(CONCAT('20', substring(area, 1, 2)) as unsigned), month = CAST(substring(area, 3, 2) as unsigned) WHERE TRUE");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE area_period DROP year, DROP month');
    }
}
