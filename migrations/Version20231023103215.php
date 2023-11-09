<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\Feedback\CategoryEnum;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231023103215 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'feedback category not allowed to be null';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('UPDATE feedback_message SET category=? WHERE true', [CategoryEnum::Feedback->value]);
        $this->addSql('ALTER TABLE feedback_message CHANGE category category VARCHAR(20) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE feedback_message CHANGE category category VARCHAR(20) DEFAULT NULL');
        $this->addSql('UPDATE feedback_message SET category=NULL WHERE true');
    }
}
