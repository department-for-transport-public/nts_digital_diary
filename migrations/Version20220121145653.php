<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220121145653 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update distance unit constants: meters -> metres';
    }

    public function up(Schema $schema): void
    {
        $this->addDistanceUnitMigrationSql('meters', 'metres');
    }

    public function down(Schema $schema): void
    {
        $this->addDistanceUnitMigrationSql('metres', 'meters');
    }

    protected function addDistanceUnitMigrationSql(string $changeFrom, string $changeTo): void
    {
        $params = [
            'changeFrom' => $changeFrom,
            'changeTo' => $changeTo,
        ];

        $this->addSql('UPDATE stage SET distance_travelled_unit = :changeTo WHERE distance_travelled_unit = :changeFrom', $params);
    }
}
