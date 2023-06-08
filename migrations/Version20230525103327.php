<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230525103327 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'fix issue with null values being ignored on unique indexes (user/training interviewer)';
    }

    public function up(Schema $schema): void
    {
        $duplicateResults = $this->connection->executeQuery("SELECT * FROM (SELECT username, count(id) as dupes FROM user WHERE training_interviewer_id IS NULL GROUP BY username) x WHERE dupes > 1");
        foreach ($duplicates = $duplicateResults->fetchAllAssociative() as $dupe) {
            $this->warnIf(true, "Duplicate: {$dupe['username']} ({$dupe['dupes']})");
        }
        $this->abortIf(!empty($duplicates), "Duplicate usernames found (see warnings above). Resolve before migrating");

        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_8D93D649F85E0677FE70D355 ON user');
        $this->addSql('ALTER TABLE user ADD virtual_column_training_interviewer_id VARCHAR(26) GENERATED ALWAYS AS (ifNull(training_interviewer_id, \'no-interviewer\')) VIRTUAL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649F85E0677A553D345 ON user (username, virtual_column_training_interviewer_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_8D93D649F85E0677A553D345 ON user');
        $this->addSql('ALTER TABLE user DROP virtual_column_training_interviewer_id');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649F85E0677FE70D355 ON user (username, training_interviewer_id)');
    }
}
