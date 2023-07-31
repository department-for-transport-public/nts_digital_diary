<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Brick\Math\BigDecimal;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230622145635 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Persist hasCost along with CostOrNil fields';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE stage ADD parking_cost_has_cost TINYINT(1) DEFAULT NULL, ADD ticket_cost_has_cost TINYINT(1) DEFAULT NULL');

        $resultSet = $this->getNonNullParkingAndTicketStageEntries();
        while(($data = $resultSet->fetchAssociative()) !== false) {
            $id = $data['id'];
            $this->addHasCostSql($id, 'parking_cost', $data['parking_cost_cost']);
            $this->addHasCostSql($id, 'ticket_cost', $data['ticket_cost_cost']);
        }
    }

    public function addHasCostSql(string $stageId, string $fieldPrefix, ?string $cost): void
    {
        $costField = "{$fieldPrefix}_cost";
        $hasCostField = "{$fieldPrefix}_has_cost";

        if ($cost === null) {
            $hasCost = null;
        } else {
            $decimal = BigDecimal::of($cost);
            $hasCost = $decimal->isZero() ? 0 : 1;
        }

        $this->addSql("UPDATE stage SET {$hasCostField}=:hasCost WHERE id=:id", [
            'hasCost' => $hasCost,
            'id' => $stageId,
        ]);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE stage DROP parking_cost_has_cost, DROP ticket_cost_has_cost');
    }

    protected function getNonNullParkingAndTicketStageEntries(): Result
    {
        $query = <<<EOQ
SELECT s.id, s.parking_cost_cost, s.ticket_cost_cost
FROM stage s 
WHERE 
    s.parking_cost_cost IS NOT NULL OR  
    s.ticket_cost_cost IS NOT NULL
;
EOQ;

        return $this->connection->executeQuery($query);
    }
}
