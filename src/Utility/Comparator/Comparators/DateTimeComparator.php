<?php

namespace App\Utility\Comparator\Comparators;

use App\Utility\Comparator\ComparatorInterface;
use DateTimeInterface;

class DateTimeComparator implements ComparatorInterface
{
    public const COMPARE_TIMES_ONLY = 'compare-times-only';

    public static function canCompareEquality($a, $b): bool
    {
        return
            !($a === null && $b === null) &&
            ($a instanceof DateTimeInterface || $a === null) &&
            ($b instanceof DateTimeInterface || $b === null);
    }

    public static function areEqual($a, $b, array $options = []): bool
    {
        /** @var DateTimeInterface $a */
        /** @var DateTimeInterface $b */
        if (in_array(self::COMPARE_TIMES_ONLY, $options)) {
            $wipeDatePart = function(?\DateTimeInterface $dateTime) {
                return $dateTime === null ?
                    null :
                    (\DateTime::createFromInterface($dateTime))->setDate(1970, 1, 1);
            };

            $a = $wipeDatePart($a);
            $b = $wipeDatePart($b);
        }

        return $a == $b;
    }
}