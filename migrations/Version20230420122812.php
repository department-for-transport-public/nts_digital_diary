<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230420122812 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add training interviewer to user - to track when a user has been created as part of training';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user ADD training_interviewer_id VARCHAR(26) DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649FE70D355 FOREIGN KEY (training_interviewer_id) REFERENCES interviewer (id)');
        $this->addSql('CREATE INDEX IDX_8D93D649FE70D355 ON user (training_interviewer_id)');
        $this->addSQL('DROP INDEX UNIQ_8D93D649F85E0677 on user');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649F85E0677FE70D355 ON user (username, training_interviewer_id)');
        $this->addSql(<<<EOF
update user set
    training_interviewer_id = upper(substring_index(substring_index(username, ':', 2), ':', -1))
where username like "int-trng:%";
EOF
        );
        $this->addSql(<<<EOF
update user set
    username = substring_index(username, ':', -1)
where username like "int-trng:%";
EOF
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<EOF
update user set
   username = CONCAT("int-trng:", training_interviewer_id, ":", username)
where training_interviewer_id is not null;
EOF
        );

        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D649FE70D355');
        $this->addSql('DROP INDEX IDX_8D93D649FE70D355 ON user');
        $this->addSql('DROP INDEX UNIQ_8D93D649F85E0677FE70D355 ON user');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649F85E0677 ON user (username)');
        $this->addSql('ALTER TABLE user DROP training_interviewer_id');
    }
}
