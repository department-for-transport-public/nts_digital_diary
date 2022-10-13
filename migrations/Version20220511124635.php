<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220511124635 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove redundant alpha-related notification fields';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP TABLE diary_keeper_notification');
        $this->addSql('DROP TABLE household_notification');
        $this->addSql('DROP TABLE journey_notification');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE diary_keeper_notification (id VARCHAR(26) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, linked_entity_id VARCHAR(26) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, sub_type VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, is_diary_keeper_acknowledged TINYINT(1) NOT NULL, is_interviewer_acknowledged TINYINT(1) NOT NULL, INDEX IDX_FA99F4DC9727B256 (linked_entity_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE household_notification (id VARCHAR(26) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, linked_entity_id VARCHAR(26) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, sub_type VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, is_diary_keeper_acknowledged TINYINT(1) NOT NULL, is_interviewer_acknowledged TINYINT(1) NOT NULL, INDEX IDX_1AF988159727B256 (linked_entity_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE journey_notification (id VARCHAR(26) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, linked_entity_id VARCHAR(26) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, sub_type VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, is_diary_keeper_acknowledged TINYINT(1) NOT NULL, is_interviewer_acknowledged TINYINT(1) NOT NULL, INDEX IDX_4F5F2E79727B256 (linked_entity_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE diary_keeper_notification ADD CONSTRAINT FK_FA99F4DC9727B256 FOREIGN KEY (linked_entity_id) REFERENCES diary_keeper (id)');
        $this->addSql('ALTER TABLE household_notification ADD CONSTRAINT FK_1AF988159727B256 FOREIGN KEY (linked_entity_id) REFERENCES household (id)');
        $this->addSql('ALTER TABLE journey_notification ADD CONSTRAINT FK_4F5F2E79727B256 FOREIGN KEY (linked_entity_id) REFERENCES journey (id)');
    }
}
