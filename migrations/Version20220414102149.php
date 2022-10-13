<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220414102149 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add serialId to interviewer table';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE interviewer ADD serial_id VARCHAR(10) NOT NULL');
        // give existing interviewers a serial number
        $this->addSql('UPDATE interviewer i JOIN (SELECT Id, @rownum:=@rownum+1 rownum FROM interviewer CROSS JOIN (select @rownum := 0) rn order by interviewer.id) as sub on sub.id = i.id SET i.serial_id = sub.rownum');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_731DDD60AF82D095 ON interviewer (serial_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_731DDD60AF82D095 ON interviewer');
        $this->addSql('ALTER TABLE interviewer DROP serial_id');
    }
}
