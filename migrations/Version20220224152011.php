<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220224152011 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add purpose_choice';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE purpose_choice (id VARCHAR(35) NOT NULL, parent_id VARCHAR(35) DEFAULT NULL, code INT DEFAULT NULL, likely_codes LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\', other_required TINYINT(1) NOT NULL, `order` INT NOT NULL, INDEX IDX_7E6124D0727ACA70 (parent_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE purpose_choice ADD CONSTRAINT FK_7E6124D0727ACA70 FOREIGN KEY (parent_id) REFERENCES purpose_choice (id)');
        $this->addSql('ALTER TABLE journey ADD purpose_choice_id VARCHAR(35) DEFAULT NULL');
        $this->addSql('ALTER TABLE journey ADD CONSTRAINT FK_C816C6A220954016 FOREIGN KEY (purpose_choice_id) REFERENCES purpose_choice (id)');
        $this->addSql('CREATE INDEX IDX_C816C6A220954016 ON journey (purpose_choice_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE journey DROP FOREIGN KEY FK_C816C6A220954016');
        $this->addSql('ALTER TABLE purpose_choice DROP FOREIGN KEY FK_7E6124D0727ACA70');
        $this->addSql('DROP TABLE purpose_choice');
        $this->addSql('DROP INDEX IDX_C816C6A220954016 ON journey');
        $this->addSql('ALTER TABLE journey DROP purpose_choice_id');
    }
}
