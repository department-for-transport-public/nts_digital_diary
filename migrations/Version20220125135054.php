<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220125135054 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Allow stage travel time to be null';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE stage CHANGE travel_time travel_time INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE stage CHANGE travel_time travel_time INT NOT NULL');
    }
}
