<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220705135638 extends AbstractMigration
{
    const RENAMES = [
        'distanceTravelled.unit' => 'distanceTravelledUnit',
        'distanceTravelled.value' => 'distanceTravelled',
        'isStartHome' => 'startIsHome',
        'isEndHome' => 'endIsHome',
    ];

    public function getDescription(): string
    {
        return 'Update property change log in line with new ChangeSet normalizers';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DELETE FROM property_change_log WHERE property_name = :prop1 OR property_name = :prop2', [
            'prop1' => 'vehicle',
            'prop2' => 'vehicleOther',
        ]);

        $this->doRenames(self::RENAMES);
    }

    public function down(Schema $schema): void
    {
        $this->doRenames(array_flip(self::RENAMES));
    }

    protected function doRenames(array $renames): void
    {
        foreach ($renames as $from => $to) {
            $this->addSql('UPDATE property_change_log p SET p.property_name = :to WHERE p.property_name = :from', [
                'from' => $from,
                'to' => $to,
            ]);
        }
    }
}
