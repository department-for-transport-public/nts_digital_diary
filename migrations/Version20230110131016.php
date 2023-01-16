<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230110131016 extends AbstractMigration
{
    protected array $map = [
        'distanceTravelled' => 'distance',
        'distanceTravelledUnit' => 'distanceUnit',
    ];

    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        foreach ($this->map as $k => $v) {
            $this->updateChangeSetProperty($k, $v);
        }

        // Method is not required in property change log, as it is not allowed to be changed
        $this->addSql(<<<SQL
            DELETE FROM property_change_log
            WHERE property_name IN ('methodOther', 'method')
        SQL
        );
    }

    public function down(Schema $schema): void
    {
    }

    protected function updateChangeSetProperty(string $from, string $to)
    {
        $this->addSql(<<<SQL
            UPDATE property_change_log
            SET property_name = '{$to}'
            WHERE property_name = '{$from}'
        SQL
        );
    }

}
