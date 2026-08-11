<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251008120730 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add notify api response table.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE notify_api_response (event_name VARCHAR(32) NOT NULL, message_class VARCHAR(255) NOT NULL, data JSON NOT NULL, timestamp DATETIME NOT NULL, recipient_hash VARCHAR(64) DEFAULT NULL, recipient VARCHAR(1024) DEFAULT NULL, id VARCHAR(26) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE user_notify_api_response (user_id VARCHAR(26) NOT NULL, notify_api_response_id VARCHAR(26) NOT NULL, INDEX IDX_719A1421A76ED395 (user_id), INDEX IDX_719A1421D6C71CEF (notify_api_response_id), PRIMARY KEY (user_id, notify_api_response_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE user_notify_api_response ADD CONSTRAINT FK_719A1421A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_notify_api_response ADD CONSTRAINT FK_719A1421D6C71CEF FOREIGN KEY (notify_api_response_id) REFERENCES notify_api_response (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE diary_keeper ADD week_start_reminder_sent_at DATETIME DEFAULT NULL, DROP has_used_practice_day');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user_notify_api_response DROP FOREIGN KEY FK_719A1421A76ED395');
        $this->addSql('ALTER TABLE user_notify_api_response DROP FOREIGN KEY FK_719A1421D6C71CEF');
        $this->addSql('DROP TABLE notify_api_response');
        $this->addSql('DROP TABLE user_notify_api_response');
        $this->addSql('ALTER TABLE diary_keeper ADD has_used_practice_day TINYINT(1) DEFAULT NULL, DROP week_start_reminder_sent_at');
    }
}
