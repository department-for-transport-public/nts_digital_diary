<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230522135557 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'interviewer empty days verification';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE diary_keeper ADD empty_days_verified_by VARCHAR(255) DEFAULT NULL, ADD empty_days_verified_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE diary_keeper DROP empty_days_verified_by, DROP empty_days_verified_at');
    }
}
