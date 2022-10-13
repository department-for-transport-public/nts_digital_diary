<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20211101124912 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create sessions table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<EOF
CREATE TABLE `sessions` (
    `sess_id` VARBINARY(128) NOT NULL PRIMARY KEY,
    `sess_data` BLOB NOT NULL,
    `sess_lifetime` INTEGER UNSIGNED NOT NULL,
    `sess_time` INTEGER UNSIGNED NOT NULL,
    INDEX `sessions_sess_lifetime_idx` (`sess_lifetime`)
) COLLATE utf8mb4_bin, ENGINE = InnoDB;
EOF);
    }

    public function down(Schema $schema): void
    {
        $this->addSql("drop table `sessions`");
    }
}
