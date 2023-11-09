<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231109100712 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix method code for ferry';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE method SET code=? where description_translation_key = ?', [24, 'ferry']);
    }

    public function down(Schema $schema): void
    {
    }
}
