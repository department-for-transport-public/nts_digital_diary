<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240815111127 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Minor column type changes and removal of comments due to DBAL upgrade';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE diary_keeper CHANGE approval_checklist_verified_at approval_checklist_verified_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE interviewer_training_record CHANGE created_at created_at DATETIME NOT NULL, CHANGE started_at started_at DATETIME DEFAULT NULL, CHANGE completed_at completed_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE maintenance_lock CHANGE whitelisted_ips whitelisted_ips JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE metrics_log CHANGE created_at created_at DATETIME NOT NULL, CHANGE metadata metadata JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE property_change_log CHANGE property_value property_value JSON DEFAULT NULL, CHANGE timestamp timestamp DATETIME(6) NOT NULL');
        $this->addSql('ALTER TABLE sessions CHANGE sess_data sess_data LONGBLOB NOT NULL');
        $this->addSql('ALTER TABLE sessions RENAME INDEX sessions_sess_lifetime_idx TO sess_lifetime_idx');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE diary_keeper CHANGE approval_checklist_verified_at approval_checklist_verified_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE interviewer_training_record CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE started_at started_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE completed_at completed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE maintenance_lock CHANGE whitelisted_ips whitelisted_ips LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE metrics_log CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE metadata metadata LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE property_change_log CHANGE property_value property_value LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\', CHANGE timestamp timestamp DATETIME NOT NULL COMMENT \'(DC2Type:datetimemicrosecond)\'');
        $this->addSql('ALTER TABLE sessions CHANGE sess_data sess_data MEDIUMBLOB NOT NULL');
        $this->addSql('ALTER TABLE sessions RENAME INDEX sess_lifetime_idx TO sessions_sess_lifetime_idx');
    }
}
