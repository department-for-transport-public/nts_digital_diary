<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20211216155329 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initial schema';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE area_period (id VARCHAR(26) NOT NULL, area INT NOT NULL, start_date DATE NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE area_period_interviewer (area_period_id VARCHAR(26) NOT NULL, interviewer_id VARCHAR(26) NOT NULL, INDEX IDX_F2E9459C79F73F02 (area_period_id), INDEX IDX_F2E9459C7906D9E8 (interviewer_id), PRIMARY KEY(area_period_id, interviewer_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE audit_log (id VARCHAR(26) NOT NULL, category VARCHAR(16) NOT NULL, username VARCHAR(255) NOT NULL, entity_id VARCHAR(255) NOT NULL, entity_class VARCHAR(255) NOT NULL, timestamp DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetimemicrosecond)\', data LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE diary_day (id VARCHAR(26) NOT NULL, diary_keeper_id VARCHAR(26) NOT NULL, number INT NOT NULL, diary_keeper_notes LONGTEXT DEFAULT NULL, interviewer_notes LONGTEXT DEFAULT NULL, coder_notes LONGTEXT DEFAULT NULL, INDEX IDX_3E44F9C5760BAD20 (diary_keeper_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE diary_keeper (id VARCHAR(26) NOT NULL, household_id VARCHAR(26) NOT NULL, proxy_id VARCHAR(26) DEFAULT NULL, is_adult TINYINT(1) NOT NULL, name VARCHAR(255) NOT NULL, number INT NOT NULL, is_proxied TINYINT(1) NOT NULL, INDEX IDX_3083496AE79FF843 (household_id), INDEX IDX_3083496ADB26A4E (proxy_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE diary_keeper_notification (id VARCHAR(26) NOT NULL, linked_entity_id VARCHAR(26) NOT NULL, sub_type VARCHAR(255) NOT NULL, is_diary_keeper_acknowledged TINYINT(1) NOT NULL, is_interviewer_acknowledged TINYINT(1) NOT NULL, INDEX IDX_FA99F4DC9727B256 (linked_entity_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE household (id VARCHAR(26) NOT NULL, area_period_id VARCHAR(26) NOT NULL, address_number SMALLINT NOT NULL, household_number SMALLINT NOT NULL, diary_week_start_date DATE DEFAULT NULL, is_onboarding_complete TINYINT(1) NOT NULL, INDEX IDX_54C32FC079F73F02 (area_period_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE household_notification (id VARCHAR(26) NOT NULL, linked_entity_id VARCHAR(26) NOT NULL, sub_type VARCHAR(255) NOT NULL, is_diary_keeper_acknowledged TINYINT(1) NOT NULL, is_interviewer_acknowledged TINYINT(1) NOT NULL, INDEX IDX_1AF988159727B256 (linked_entity_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE interviewer (id VARCHAR(26) NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE journey (id VARCHAR(26) NOT NULL, diary_day_id VARCHAR(26) NOT NULL, purpose_id INT DEFAULT NULL, purpose_other VARCHAR(255) DEFAULT NULL, start_time TIME NOT NULL, end_time TIME DEFAULT NULL, start_location VARCHAR(255) DEFAULT NULL, end_location VARCHAR(255) DEFAULT NULL, is_start_home TINYINT(1) NOT NULL, is_end_home TINYINT(1) DEFAULT NULL, is_partial TINYINT(1) NOT NULL, INDEX IDX_C816C6A245EA4ED8 (diary_day_id), INDEX IDX_C816C6A27FC21131 (purpose_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE journey_notification (id VARCHAR(26) NOT NULL, linked_entity_id VARCHAR(26) NOT NULL, sub_type VARCHAR(255) NOT NULL, is_diary_keeper_acknowledged TINYINT(1) NOT NULL, is_interviewer_acknowledged TINYINT(1) NOT NULL, INDEX IDX_4F5F2E79727B256 (linked_entity_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE maintenance_lock (id VARCHAR(26) NOT NULL, whitelisted_ips LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE method (id INT NOT NULL, code INT DEFAULT NULL, description_translation_key VARCHAR(30) NOT NULL, type VARCHAR(10) NOT NULL, display_group VARCHAR(10) NOT NULL, sort INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE otp_user (id VARCHAR(26) NOT NULL, area_period_id VARCHAR(26) NOT NULL, household_id VARCHAR(26) DEFAULT NULL, user_identifier VARCHAR(10) NOT NULL, INDEX IDX_6978C5C879F73F02 (area_period_id), UNIQUE INDEX UNIQ_6978C5C8E79FF843 (household_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE purpose (id INT AUTO_INCREMENT NOT NULL, code INT DEFAULT NULL, description_translation_key VARCHAR(30) NOT NULL, is_escort TINYINT(1) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE stage (id VARCHAR(26) NOT NULL, journey_id VARCHAR(26) NOT NULL, method_id INT NOT NULL, vehicle_id VARCHAR(26) DEFAULT NULL, ticket_type_id INT DEFAULT NULL, method_other VARCHAR(255) DEFAULT NULL, travel_time INT NOT NULL, adult_count INT DEFAULT NULL, child_count INT DEFAULT NULL, vehicle_other VARCHAR(255) DEFAULT NULL, is_driver TINYINT(1) DEFAULT NULL, parking_cost INT DEFAULT NULL, is_ticket_adult TINYINT(1) DEFAULT NULL, is_ticket_return TINYINT(1) DEFAULT NULL, is_ticket_concessionary TINYINT(1) DEFAULT NULL, ticket_type_other VARCHAR(255) DEFAULT NULL, ticket_cost INT DEFAULT NULL, boarding_count INT DEFAULT NULL, number INT NOT NULL, distance_travelled_value NUMERIC(8, 1) DEFAULT NULL, distance_travelled_unit VARCHAR(12) DEFAULT NULL, INDEX IDX_C27C9369D5C9896F (journey_id), INDEX IDX_C27C936919883967 (method_id), INDEX IDX_C27C9369545317D1 (vehicle_id), INDEX IDX_C27C9369C980D5C1 (ticket_type_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE ticket_type (id INT NOT NULL, description_translation_key VARCHAR(30) NOT NULL, is_other_property_required TINYINT(1) NOT NULL, is_clear_return_ticket_cost TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id VARCHAR(26) NOT NULL, interviewer_id VARCHAR(26) DEFAULT NULL, diary_keeper_id VARCHAR(26) DEFAULT NULL, username VARCHAR(180) NOT NULL, UNIQUE INDEX UNIQ_8D93D649F85E0677 (username), UNIQUE INDEX UNIQ_8D93D6497906D9E8 (interviewer_id), UNIQUE INDEX UNIQ_8D93D649760BAD20 (diary_keeper_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE vehicle (id VARCHAR(26) NOT NULL, household_id VARCHAR(26) NOT NULL, primary_driver_id VARCHAR(26) DEFAULT NULL, method_id INT NOT NULL, registration_number VARCHAR(10) DEFAULT NULL, friendly_name VARCHAR(255) NOT NULL, INDEX IDX_1B80E486E79FF843 (household_id), INDEX IDX_1B80E4869D984EE (primary_driver_id), INDEX IDX_1B80E48619883967 (method_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE area_period_interviewer ADD CONSTRAINT FK_F2E9459C79F73F02 FOREIGN KEY (area_period_id) REFERENCES area_period (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE area_period_interviewer ADD CONSTRAINT FK_F2E9459C7906D9E8 FOREIGN KEY (interviewer_id) REFERENCES interviewer (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE diary_day ADD CONSTRAINT FK_3E44F9C5760BAD20 FOREIGN KEY (diary_keeper_id) REFERENCES diary_keeper (id)');
        $this->addSql('ALTER TABLE diary_keeper ADD CONSTRAINT FK_3083496AE79FF843 FOREIGN KEY (household_id) REFERENCES household (id)');
        $this->addSql('ALTER TABLE diary_keeper ADD CONSTRAINT FK_3083496ADB26A4E FOREIGN KEY (proxy_id) REFERENCES diary_keeper (id)');
        $this->addSql('ALTER TABLE diary_keeper_notification ADD CONSTRAINT FK_FA99F4DC9727B256 FOREIGN KEY (linked_entity_id) REFERENCES diary_keeper (id)');
        $this->addSql('ALTER TABLE household ADD CONSTRAINT FK_54C32FC079F73F02 FOREIGN KEY (area_period_id) REFERENCES area_period (id)');
        $this->addSql('ALTER TABLE household_notification ADD CONSTRAINT FK_1AF988159727B256 FOREIGN KEY (linked_entity_id) REFERENCES household (id)');
        $this->addSql('ALTER TABLE journey ADD CONSTRAINT FK_C816C6A245EA4ED8 FOREIGN KEY (diary_day_id) REFERENCES diary_day (id)');
        $this->addSql('ALTER TABLE journey ADD CONSTRAINT FK_C816C6A27FC21131 FOREIGN KEY (purpose_id) REFERENCES purpose (id)');
        $this->addSql('ALTER TABLE journey_notification ADD CONSTRAINT FK_4F5F2E79727B256 FOREIGN KEY (linked_entity_id) REFERENCES journey (id)');
        $this->addSql('ALTER TABLE otp_user ADD CONSTRAINT FK_6978C5C879F73F02 FOREIGN KEY (area_period_id) REFERENCES area_period (id)');
        $this->addSql('ALTER TABLE otp_user ADD CONSTRAINT FK_6978C5C8E79FF843 FOREIGN KEY (household_id) REFERENCES household (id)');
        $this->addSql('ALTER TABLE stage ADD CONSTRAINT FK_C27C9369D5C9896F FOREIGN KEY (journey_id) REFERENCES journey (id)');
        $this->addSql('ALTER TABLE stage ADD CONSTRAINT FK_C27C936919883967 FOREIGN KEY (method_id) REFERENCES method (id)');
        $this->addSql('ALTER TABLE stage ADD CONSTRAINT FK_C27C9369545317D1 FOREIGN KEY (vehicle_id) REFERENCES vehicle (id)');
        $this->addSql('ALTER TABLE stage ADD CONSTRAINT FK_C27C9369C980D5C1 FOREIGN KEY (ticket_type_id) REFERENCES ticket_type (id)');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D6497906D9E8 FOREIGN KEY (interviewer_id) REFERENCES interviewer (id)');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649760BAD20 FOREIGN KEY (diary_keeper_id) REFERENCES diary_keeper (id)');
        $this->addSql('ALTER TABLE vehicle ADD CONSTRAINT FK_1B80E486E79FF843 FOREIGN KEY (household_id) REFERENCES household (id)');
        $this->addSql('ALTER TABLE vehicle ADD CONSTRAINT FK_1B80E4869D984EE FOREIGN KEY (primary_driver_id) REFERENCES diary_keeper (id)');
        $this->addSql('ALTER TABLE vehicle ADD CONSTRAINT FK_1B80E48619883967 FOREIGN KEY (method_id) REFERENCES method (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE area_period_interviewer DROP FOREIGN KEY FK_F2E9459C79F73F02');
        $this->addSql('ALTER TABLE household DROP FOREIGN KEY FK_54C32FC079F73F02');
        $this->addSql('ALTER TABLE otp_user DROP FOREIGN KEY FK_6978C5C879F73F02');
        $this->addSql('ALTER TABLE journey DROP FOREIGN KEY FK_C816C6A245EA4ED8');
        $this->addSql('ALTER TABLE diary_day DROP FOREIGN KEY FK_3E44F9C5760BAD20');
        $this->addSql('ALTER TABLE diary_keeper DROP FOREIGN KEY FK_3083496ADB26A4E');
        $this->addSql('ALTER TABLE diary_keeper_notification DROP FOREIGN KEY FK_FA99F4DC9727B256');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D649760BAD20');
        $this->addSql('ALTER TABLE vehicle DROP FOREIGN KEY FK_1B80E4869D984EE');
        $this->addSql('ALTER TABLE diary_keeper DROP FOREIGN KEY FK_3083496AE79FF843');
        $this->addSql('ALTER TABLE household_notification DROP FOREIGN KEY FK_1AF988159727B256');
        $this->addSql('ALTER TABLE otp_user DROP FOREIGN KEY FK_6978C5C8E79FF843');
        $this->addSql('ALTER TABLE vehicle DROP FOREIGN KEY FK_1B80E486E79FF843');
        $this->addSql('ALTER TABLE area_period_interviewer DROP FOREIGN KEY FK_F2E9459C7906D9E8');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D6497906D9E8');
        $this->addSql('ALTER TABLE journey_notification DROP FOREIGN KEY FK_4F5F2E79727B256');
        $this->addSql('ALTER TABLE stage DROP FOREIGN KEY FK_C27C9369D5C9896F');
        $this->addSql('ALTER TABLE stage DROP FOREIGN KEY FK_C27C936919883967');
        $this->addSql('ALTER TABLE vehicle DROP FOREIGN KEY FK_1B80E48619883967');
        $this->addSql('ALTER TABLE journey DROP FOREIGN KEY FK_C816C6A27FC21131');
        $this->addSql('ALTER TABLE stage DROP FOREIGN KEY FK_C27C9369C980D5C1');
        $this->addSql('ALTER TABLE stage DROP FOREIGN KEY FK_C27C9369545317D1');
        $this->addSql('DROP TABLE area_period');
        $this->addSql('DROP TABLE area_period_interviewer');
        $this->addSql('DROP TABLE audit_log');
        $this->addSql('DROP TABLE diary_day');
        $this->addSql('DROP TABLE diary_keeper');
        $this->addSql('DROP TABLE diary_keeper_notification');
        $this->addSql('DROP TABLE household');
        $this->addSql('DROP TABLE household_notification');
        $this->addSql('DROP TABLE interviewer');
        $this->addSql('DROP TABLE journey');
        $this->addSql('DROP TABLE journey_notification');
        $this->addSql('DROP TABLE maintenance_lock');
        $this->addSql('DROP TABLE method');
        $this->addSql('DROP TABLE otp_user');
        $this->addSql('DROP TABLE purpose');
        $this->addSql('DROP TABLE stage');
        $this->addSql('DROP TABLE ticket_type');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE vehicle');
    }
}
