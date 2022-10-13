<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\Journey\Journey;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220510101638 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix propertyChangeLog time values';
    }

    public function up(Schema $schema): void
    {
        $this->migrateValues(
            fn(string $oldValue, int $utcSecondsOffset) =>
            \DateTime::createFromFormat('U', $oldValue)->modify("+{$utcSecondsOffset}seconds")->format('H:i')
        );
    }

    public function down(Schema $schema): void
    {
        $this->migrateValues(
            fn(string $oldValue) =>
            \DateTime::createFromFormat('Y-m-d H:i', "1970-01-01 {$oldValue}")->format('U')
        );
    }

    protected function migrateValues(callable $oldValueToNewValue): void
    {
        $utcSecondsOffset = (new \DateTimeZone(date_default_timezone_get()))->getOffset(new \DateTime());
        foreach($this->getData() as $data) {
            $oldValue = json_decode($data['property_value']);
            $this->addSql('UPDATE property_change_log SET property_value=:newValue WHERE id=:id', [
                'id' => $data['id'],
                'newValue' => json_encode($oldValueToNewValue($oldValue, $utcSecondsOffset)),
            ]);
        }
    }

    protected function getData(): array
    {
        return $this->connection->executeQuery('SELECT p.id, p.property_value FROM property_change_log p WHERE p.entity_class=:entityClass '.
            'AND (p.property_name = :startTime OR p.property_name = :endTime)', [
            'entityClass' => Journey::class,
            'startTime' => 'startTime',
            'endTime' => 'endTime',
        ])->fetchAllAssociative();
    }
}
