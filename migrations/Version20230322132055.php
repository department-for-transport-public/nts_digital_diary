<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230322132055 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add vehicle odometer readings and primary driver';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE vehicle ADD primary_driver_id VARCHAR(26) DEFAULT NULL, ADD week_start_odometer_reading INT DEFAULT NULL, ADD week_end_odometer_reading INT DEFAULT NULL, ADD odometer_unit VARCHAR(12) DEFAULT NULL, DROP registration_number');
        $this->addSql('ALTER TABLE vehicle ADD CONSTRAINT FK_1B80E4869D984EE FOREIGN KEY (primary_driver_id) REFERENCES diary_keeper (id)');
        $this->addSql('CREATE INDEX IDX_1B80E4869D984EE ON vehicle (primary_driver_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE vehicle DROP FOREIGN KEY FK_1B80E4869D984EE');
        $this->addSql('DROP INDEX IDX_1B80E4869D984EE ON vehicle');
        $this->addSql('ALTER TABLE vehicle ADD registration_number VARCHAR(10) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, DROP primary_driver_id, DROP week_start_odometer_reading, DROP week_end_odometer_reading, DROP odometer_unit');
    }
}
