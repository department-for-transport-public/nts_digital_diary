<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220329082430 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Simplify stage->ticketType to single text field, remove other ticket type options';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE stage DROP FOREIGN KEY FK_C27C9369C980D5C1');
        $this->addSql('DROP TABLE ticket_type');
        $this->addSql('DROP INDEX IDX_C27C9369C980D5C1 ON stage');
        $this->addSql('ALTER TABLE stage DROP ticket_type_id, DROP is_ticket_adult, DROP is_ticket_return, DROP is_ticket_concessionary, CHANGE ticket_type_other ticket_type VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE ticket_type (id INT NOT NULL, description_translation_key VARCHAR(30) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, is_other_property_required TINYINT(1) NOT NULL, is_clear_return_ticket_cost TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE stage ADD ticket_type_id INT DEFAULT NULL, ADD is_ticket_adult TINYINT(1) DEFAULT NULL, ADD is_ticket_return TINYINT(1) DEFAULT NULL, ADD is_ticket_concessionary TINYINT(1) DEFAULT NULL, CHANGE ticket_type ticket_type_other VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE stage ADD CONSTRAINT FK_C27C9369C980D5C1 FOREIGN KEY (ticket_type_id) REFERENCES ticket_type (id)');
        $this->addSql('CREATE INDEX IDX_C27C9369C980D5C1 ON stage (ticket_type_id)');
    }
}
