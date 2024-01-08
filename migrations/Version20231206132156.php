<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20231206132156 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add satisfaction survey';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE satisfaction_survey (id VARCHAR(26) NOT NULL, diarykeeper_id VARCHAR(26) DEFAULT NULL, ease_rating VARCHAR(32) NOT NULL, burden_rating VARCHAR(32) NOT NULL, burden_reason JSON NOT NULL, burden_reason_other LONGTEXT DEFAULT NULL, type_of_devices JSON NOT NULL, type_of_devices_other LONGTEXT DEFAULT NULL, how_often_entries_added VARCHAR(32) NOT NULL, written_note_kept TINYINT(1) NOT NULL, preferred_method VARCHAR(32) NOT NULL, preferred_method_other LONGTEXT DEFAULT NULL, UNIQUE INDEX UNIQ_F9AA62FB4F3C9F69 (diarykeeper_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE satisfaction_survey ADD CONSTRAINT FK_F9AA62FB4F3C9F69 FOREIGN KEY (diarykeeper_id) REFERENCES diary_keeper (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE satisfaction_survey DROP FOREIGN KEY FK_F9AA62FB4F3C9F69');
        $this->addSql('DROP TABLE satisfaction_survey');
    }
}
