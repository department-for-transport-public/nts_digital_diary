<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220511122353 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove Vehicle->primaryDriver and DiaryKeeper->primaryDriverVehicles';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE vehicle DROP FOREIGN KEY FK_1B80E4869D984EE');
        $this->addSql('DROP INDEX IDX_1B80E4869D984EE ON vehicle');
        $this->addSql('ALTER TABLE vehicle DROP primary_driver_id');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE vehicle ADD primary_driver_id VARCHAR(26) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE vehicle ADD CONSTRAINT FK_1B80E4869D984EE FOREIGN KEY (primary_driver_id) REFERENCES diary_keeper (id)');
        $this->addSql('CREATE INDEX IDX_1B80E4869D984EE ON vehicle (primary_driver_id)');
    }
}
