<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220421131647 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'DiaryKeeper - M2M proxies';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE diary_keeper_proxies (diary_keeper_source VARCHAR(26) NOT NULL, diary_keeper_target VARCHAR(26) NOT NULL, INDEX IDX_C97237EFA745E21C (diary_keeper_source), INDEX IDX_C97237EFBEA0B293 (diary_keeper_target), PRIMARY KEY(diary_keeper_source, diary_keeper_target)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE diary_keeper_proxies ADD CONSTRAINT FK_C97237EFA745E21C FOREIGN KEY (diary_keeper_source) REFERENCES diary_keeper (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE diary_keeper_proxies ADD CONSTRAINT FK_C97237EFBEA0B293 FOREIGN KEY (diary_keeper_target) REFERENCES diary_keeper (id) ON DELETE CASCADE');

        $results = $this->connection->executeQuery('SELECT dk.id, dk.proxy_id FROM diary_keeper dk WHERE dk.is_proxied = 1 AND dk.proxy_id IS NOT NULL');

        foreach($results->fetchAllAssociative() as $result) {
            $this->addSql('INSERT INTO diary_keeper_proxies (diary_keeper_source, diary_keeper_target) VALUES(:source, :target)', [
                'source' => $result['id'],
                'target' => $result['proxy_id'],
            ]);
        }

        $this->addSql('ALTER TABLE diary_keeper DROP FOREIGN KEY FK_3083496ADB26A4E');
        $this->addSql('DROP INDEX IDX_3083496ADB26A4E ON diary_keeper');
        $this->addSql('ALTER TABLE diary_keeper DROP proxy_id');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE diary_keeper ADD proxy_id VARCHAR(26) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE diary_keeper ADD CONSTRAINT FK_3083496ADB26A4E FOREIGN KEY (proxy_id) REFERENCES diary_keeper (id)');
        $this->addSql('CREATE INDEX IDX_3083496ADB26A4E ON diary_keeper (proxy_id)');

        $results = $this->connection->executeQuery('SELECT dkp.diary_keeper_source, dkp.diary_keeper_target FROM diary_keeper_proxies dkp');

        foreach($results->fetchAllAssociative() as $result) {
            $this->addSql('UPDATE diary_keeper dk SET dk.proxy_id = :proxy_id, dk.is_proxied = 1 WHERE dk.id = :id', [
                'id' => $result['diary_keeper_source'],
                'proxy_id' => $result['diary_keeper_target'],
            ]);
        }

        $this->addSql('DROP TABLE diary_keeper_proxies');
    }
}
