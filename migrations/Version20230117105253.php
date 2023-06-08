<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230117105253 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add mediaType field to diary_keeper (paper/digital)';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE diary_keeper ADD media_type VARCHAR(10) NOT NULL');
        $this->addSql("UPDATE diary_keeper SET media_type = 'digital' WHERE 1");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE diary_keeper DROP media_type');
    }
}
