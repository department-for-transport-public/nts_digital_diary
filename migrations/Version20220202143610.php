<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220202143610 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add api user table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE api_user (id VARCHAR(26) NOT NULL, key_ VARCHAR(255) NOT NULL, nonce INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE api_user');
    }
}
