<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250903104527 extends AbstractMigration
{
    private array $journeyIds;

    public function getDescription(): string
    {
        return 'remove the old example journeys from practice day';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("DELETE FROM stage WHERE journey_id in ({$this->getSharedJourneyIdsSql()})");
        $this->addSql("DELETE FROM journey WHERE id in ({$this->getSharedJourneyIdsSql()})");

        $this->addSql("DELETE FROM stage WHERE journey_id in ({$this->getJourneyIdsSql()})");
        $this->addSql("DELETE FROM journey WHERE id in ({$this->getJourneyIdsSql()})");
    }

    public function down(Schema $schema): void
    {
        return;
    }

    private function getSharedJourneyIdsSql(): string
    {
        return <<<OED
select * from (
    SELECT j1.id FROM journey j1
    WHERE j1.shared_from_id in ({$this->getJourneyIdsSql()})
) as jt1
OED;
    }

    private function getJourneyIdsSql(): string
    {
        return <<<OED
select * from (
    select j0.id
    from journey j0
    join diary_day dd0 on j0.diary_day_id = dd0.id
    where dd0.number = 0
    and
    (
        (
            j0.is_start_home = 1
            and j0.end_location = 'Liverpool city centre'
            and (j0.purpose = 'Go out for meal with friends' or j0.start_time <> '19:00:00' or j0.end_time <> '20:05:00')
         ) or (
             j0.is_end_home = 1
             and j0.start_location = 'Liverpool city centre'
             and (j0.start_time = '22:30:00' or j0.end_time = '22:55:00')
        )
    )
) as jt0
OED;

    }
}
