<?php

namespace App\Utility\Comparator;

interface ComparatorInterface
{
    public static function canCompareEquality($a, $b): bool;
    public static function areEqual($a, $b, array $options = []): bool;
}