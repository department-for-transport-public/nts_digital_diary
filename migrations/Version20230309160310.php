<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230309160310 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Interviewer training';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE interviewer_training_record (id VARCHAR(26) NOT NULL, interviewer_id VARCHAR(26) NOT NULL, household_id VARCHAR(26) DEFAULT NULL, module_name VARCHAR(50) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', started_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', completed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_624086D7906D9E8 (interviewer_id), UNIQUE INDEX UNIQ_624086DE79FF843 (household_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE interviewer_training_record ADD CONSTRAINT FK_624086D7906D9E8 FOREIGN KEY (interviewer_id) REFERENCES interviewer (id)');
        $this->addSql('ALTER TABLE interviewer_training_record ADD CONSTRAINT FK_624086DE79FF843 FOREIGN KEY (household_id) REFERENCES household (id)');
        $this->addSql('DROP INDEX UNIQ_A333EF4CD7943D68BB827337 ON area_period');
        $this->addSql('ALTER TABLE area_period ADD training_interviewer_id VARCHAR(26) DEFAULT NULL');
        $this->addSql('ALTER TABLE area_period ADD CONSTRAINT FK_A333EF4CFE70D355 FOREIGN KEY (training_interviewer_id) REFERENCES interviewer (id)');
        $this->addSql('CREATE INDEX IDX_A333EF4CFE70D355 ON area_period (training_interviewer_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_A333EF4CD7943D68BB827337FE70D355 ON area_period (area, year, training_interviewer_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE interviewer_training_record');
        $this->addSql('ALTER TABLE area_period DROP FOREIGN KEY FK_A333EF4CFE70D355');
        $this->addSql('DROP INDEX IDX_A333EF4CFE70D355 ON area_period');
        $this->addSql('DROP INDEX UNIQ_A333EF4CD7943D68BB827337FE70D355 ON area_period');
        $this->addSql('ALTER TABLE area_period DROP training_interviewer_id');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_A333EF4CD7943D68BB827337 ON area_period (area, year)');
    }
}
