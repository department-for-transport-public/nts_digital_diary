<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250313160828 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'create feedback stats view';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<SQL
CREATE VIEW vw_closed_feedback_note_stats AS
SELECT UUID() as 'id', MAX(n.created_at) AS created_at, n.message_id AS message_id
FROM feedback_note n
WHERE n.note LIKE '%to CLOSED\n'
OR n.note LIKE '%to REJECTED\n'
GROUP BY message_id
SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP VIEW vw_closed_feedback_note_stats');
    }
}
