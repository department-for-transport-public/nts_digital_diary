<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20221115133932 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Delete erroneous changeLog entries that were caused by bugs';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<SQL
            DELETE FROM property_change_log
            WHERE property_name NOT IN (
                'startTime',
                'startLocation',
                'startIsHome',
                'endTime',
                'endLocation',
                'endIsHome',
                'purpose',
            
                'adultCount',
                'boardingCount',
                'childCount',
                'distanceTravelled',
                'distanceTravelledUnit',
                'isDriver',
                'method',
                'methodOther',
                'parkingCost',
                'travelTime',
                'ticketCost',
                'ticketType',
                'vehicle'
            );
            SQL);
    }

    public function down(Schema $schema): void
    {
    }
}
