<?php

namespace App\Doctrine\DBAL\Schema\MySQL;


use Doctrine\DBAL\Platforms\MySQL\Comparator;
use Doctrine\DBAL\Schema\ColumnDiff;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaDiff;

class CustomComparator extends Comparator
{
    private const IGNORE_TABLE_COLUMNS = [
        'user' => ['virtual_column_training_interviewer_id'],
    ];

    public function compareSchemas(Schema $fromSchema, Schema $toSchema): SchemaDiff
    {
        $diff = parent::compareSchemas($fromSchema, $toSchema);
        foreach ($diff->changedTables as $tableName => $tableDiff) {
            if (!isset(self::IGNORE_TABLE_COLUMNS[$tableName])) {
                continue;
            }
            $tableDiff->changedColumns = array_filter(
                $tableDiff->changedColumns,
                fn(ColumnDiff $diff) => !in_array($diff->column->getName(), self::IGNORE_TABLE_COLUMNS[$tableName])
            );
        }
        return $diff;
    }
}