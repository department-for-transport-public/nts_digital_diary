<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220401115539 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Revert purpose to using a simple text field, and remove purpose_form_test_group';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE journey DROP FOREIGN KEY FK_C816C6A27FC21131');
        $this->addSql('ALTER TABLE journey DROP FOREIGN KEY FK_C816C6A220954016');
        $this->addSql('ALTER TABLE purpose_choice DROP FOREIGN KEY FK_7E6124D0727ACA70');
        $this->addSql('DROP TABLE purpose');
        $this->addSql('DROP TABLE purpose_choice');
        $this->addSql('ALTER TABLE household DROP purpose_form_test_group');
        $this->addSql('DROP INDEX IDX_C816C6A27FC21131 ON journey');
        $this->addSql('DROP INDEX IDX_C816C6A220954016 ON journey');
        $this->addSql('ALTER TABLE journey DROP purpose_id, DROP purpose_choice_id, CHANGE purpose_other purpose VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE purpose (id INT AUTO_INCREMENT NOT NULL, code INT DEFAULT NULL, description_translation_key VARCHAR(30) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, is_escort TINYINT(1) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE purpose_choice (id VARCHAR(35) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, parent_id VARCHAR(35) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, code INT DEFAULT NULL, likely_codes LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:json)\', other_required TINYINT(1) NOT NULL, `order` INT NOT NULL, INDEX IDX_7E6124D0727ACA70 (parent_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE purpose_choice ADD CONSTRAINT FK_7E6124D0727ACA70 FOREIGN KEY (parent_id) REFERENCES purpose_choice (id)');
        $this->addSql('ALTER TABLE household ADD purpose_form_test_group VARCHAR(1) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE journey ADD purpose_id INT DEFAULT NULL, ADD purpose_choice_id VARCHAR(35) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE purpose purpose_other VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE journey ADD CONSTRAINT FK_C816C6A220954016 FOREIGN KEY (purpose_choice_id) REFERENCES purpose_choice (id)');
        $this->addSql('ALTER TABLE journey ADD CONSTRAINT FK_C816C6A27FC21131 FOREIGN KEY (purpose_id) REFERENCES purpose (id)');
        $this->addSql('CREATE INDEX IDX_C816C6A27FC21131 ON journey (purpose_id)');
        $this->addSql('CREATE INDEX IDX_C816C6A220954016 ON journey (purpose_choice_id)');
    }
}
