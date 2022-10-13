<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220414112450 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'update PropertyChangeLog to record which interviewer made a change';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE property_change_log ADD interviewer_serial_id INT DEFAULT NULL');
        $this->addSql('UPDATE property_change_log set interviewer_serial_id=1 WHERE is_interviewer=1');
        $this->addSql('ALTER TABLE property_change_log DROP is_interviewer');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE property_change_log ADD is_interviewer TINYINT(1) NOT NULL');
        $this->addSql('UPDATE property_change_log set is_interviewer=1 WHERE interviewer_serial_id IS NOT NULL');
        $this->addSql('ALTER TABLE property_change_log DROP interviewer_serial_id');
    }
}
