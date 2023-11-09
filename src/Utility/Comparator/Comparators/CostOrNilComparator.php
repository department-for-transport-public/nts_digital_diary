<?php

namespace App\Utility\Comparator\Comparators;

use App\Entity\Embeddable\CostOrNil;
use App\Utility\Comparator\ComparatorInterface;
use Brick\Math\BigDecimal;

class CostOrNilComparator implements ComparatorInterface
{
    public static function canCompareEquality($a, $b): bool
    {
        return
            !($a === null && $b === null) &&
            ($a instanceof CostOrNil || $a === null) &&
            ($b instanceof CostOrNil || $b === null);
    }

    public static function areEqual($a, $b, array $options = []): bool
    {
        if ($a === null || $b === null) {
            return false;
        }

        $valueA = $a->getCost();
        $valueB = $b->getCost();

        if ($valueA instanceof BigDecimal && $valueB instanceof BigDecimal) {
            return $valueA->isEqualTo($valueB);
        } else {
            return false;
        }
    }
}