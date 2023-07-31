<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230721150515 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'rename interviewer approval checklist fields';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE diary_keeper CHANGE empty_days_verified_by approval_checklist_verified_by VARCHAR(255) DEFAULT NULL, CHANGE empty_days_verified_at approval_checklist_verified_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE diary_keeper CHANGE approval_checklist_verified_by empty_days_verified_by VARCHAR(255) DEFAULT NULL, CHANGE approval_checklist_verified_at empty_days_verified_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }
}
