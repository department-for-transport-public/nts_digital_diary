<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230920145937 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create entities for feedback centre';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE feedback_message (id VARCHAR(26) NOT NULL, message LONGTEXT NOT NULL, email_address VARCHAR(255) DEFAULT NULL, category VARCHAR(20) DEFAULT NULL, assigned_to VARCHAR(20) DEFAULT NULL, sent DATETIME NOT NULL, state VARCHAR(20) NOT NULL, current_user_serial VARCHAR(20) DEFAULT NULL, original_user_serial VARCHAR(20) DEFAULT NULL, page VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE feedback_note (id VARCHAR(26) NOT NULL, message_id VARCHAR(26) NOT NULL, note LONGTEXT NOT NULL, user_identifier VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_A1F1B9D537A1329 (message_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE feedback_note ADD CONSTRAINT FK_A1F1B9D537A1329 FOREIGN KEY (message_id) REFERENCES feedback_message (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE feedback_note DROP FOREIGN KEY FK_A1F1B9D537A1329');
        $this->addSql('DROP TABLE feedback_message');
        $this->addSql('DROP TABLE feedback_note');
    }
}
