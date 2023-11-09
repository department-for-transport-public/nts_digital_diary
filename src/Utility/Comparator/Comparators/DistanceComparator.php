<?php

namespace App\Utility\Comparator\Comparators;

use App\Entity\Embeddable\Distance;
use App\Utility\Comparator\ComparatorInterface;
use Brick\Math\BigDecimal;

class DistanceComparator implements ComparatorInterface
{
    public static function canCompareEquality($a, $b): bool
    {
        return
            !($a === null && $b === null) &&
            ($a instanceof Distance || $a === null) &&
            ($b instanceof Distance || $b === null);
    }

    public static function areEqual($a, $b, array $options = []): bool
    {
        if ($a === null || $b === null) {
            return false;
        }

        $valueA = $a->getValue();
        $valueB = $b->getValue();

        if ($valueA instanceof BigDecimal && $valueB instanceof BigDecimal) {
            $equalValues = $valueA->isEqualTo($valueB);
        } else {
            $equalValues = $valueA === $valueB; // One or both sides are null
        }

        return $a->getUnit() === $b->getUnit() && $equalValues;
    }
}