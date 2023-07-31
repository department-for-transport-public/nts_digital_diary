<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Brick\Math\BigDecimal;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230614155743 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Convert CostOrNil fields to use decimals and rename them';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE stage CHANGE parking_cost_cost parking_cost_cost NUMERIC(10, 2) DEFAULT NULL, CHANGE ticket_cost_cost ticket_cost_cost NUMERIC(10, 2) DEFAULT NULL');
        $this->addSql('UPDATE stage SET parking_cost_cost = parking_cost_cost / 100');
        $this->addSql('UPDATE stage SET ticket_cost_cost = ticket_cost_cost / 100');

        // Distances will already be decimals, but need to be made 2dp to be consistent
        $this->migrateTicketCostAndParkingCostPropertyLogValues(
            $this->getNonNullDistancePropertyChangeLogEntries(),
            fn(BigDecimal $x) => strval($x->toScale(2))
        );

        // TicketCost / ParkingCost need to be converted from int (pence) to decimal 2dp (pounds)
        $this->migrateTicketCostAndParkingCostPropertyLogValues(
            $this->getNonNullParkingAndTicketPropertyChangeLogEntries(),
            fn(BigDecimal $x) => strval($x->toScale(2)->dividedBy(100))
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE stage SET parking_cost_cost = parking_cost_cost * 100');
        $this->addSql('UPDATE stage SET ticket_cost_cost = ticket_cost_cost * 100');
        $this->addSql('ALTER TABLE stage CHANGE parking_cost_cost parking_cost_cost INT DEFAULT NULL, CHANGE ticket_cost_cost ticket_cost_cost INT DEFAULT NULL');

        $this->migrateTicketCostAndParkingCostPropertyLogValues(
            $this->getNonNullParkingAndTicketPropertyChangeLogEntries(),
            fn(BigDecimal $x) => $x->multipliedBy(100)->toScale(0)->toInt()
        );
    }

    protected function migrateTicketCostAndParkingCostPropertyLogValues(Result $resultSet, \Closure $convert): void
    {
        $updateSql = "UPDATE property_change_log SET property_value = :value WHERE id = :id";
        while (($data = $resultSet->fetchAssociative()) !== false) {
            $decodedValue = json_decode($data['property_value']);
            if ($decodedValue === null) {
                // To deal with errant "null" strings (only found in development databases)
                $this->addSql($updateSql, ['value' => null, 'id' => $data['id']]);
            } else {
                $decimal = BigDecimal::of($decodedValue);
                $convertedValue = $convert($decimal);
                $this->addSql($updateSql, ['value' => json_encode($convertedValue), 'id' => $data['id']]);
            }
        }
    }

    protected function getNonNullParkingAndTicketPropertyChangeLogEntries(): Result
    {
        $query = <<<EOQ
SELECT p.id, p.property_value 
FROM property_change_log p 
WHERE 
    p.property_name IN ('parkingCost', 'ticketCost') AND 
    p.property_value IS NOT NULL
;
EOQ;

        return $this->connection->executeQuery($query);
    }

    protected function getNonNullDistancePropertyChangeLogEntries(): Result
    {
        $query = <<<EOQ
SELECT p.id, p.property_value 
FROM property_change_log p 
WHERE 
    p.property_name = 'distance' AND 
    p.property_value IS NOT NULL
;
EOQ;

        return $this->connection->executeQuery($query);
    }

}
