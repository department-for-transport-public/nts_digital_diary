<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220127112332 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add diaryState field to DiaryKeeper';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE diary_keeper ADD diary_state VARCHAR(20) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE diary_keeper DROP diary_state');
    }
}
