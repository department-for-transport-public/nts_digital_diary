<?php

namespace App\Utility;

class ReorderUtils
{
    /*
     * Takes an array of objects, and optionally a mapping which is used
     * to reorder the objects. The mapping should comprise of a comma-separated
     * list of integers, starting at 1.
     *
     * If the mapping is missing or invalid, the array of items will be returned
     * in its original order.
     */
    public static function getSortedItems(array $items, ?string $mapping): array
    {
        if ($mapping && preg_match('/^\d+(,\d+)*$/', $mapping)) {
            $mapping = explode(',', $mapping);
        } else {
            $mapping = null;
        }

        if (!$mapping || !self::isMappingValid($mapping, count($items))) {
            $mapping = range(1, count($items));
        }

        $sortedItems = [];
        for($i=0; $i<count($mapping); $i++) {
            $sortedItems[$i] = $items[$mapping[$i] - 1];
        }

        return $sortedItems;
    }

    /*
     * Takes an array of numbers and a count of how many items there should be.
     * Checks that every number from 1->n is present, and unique in the array.
     */
    public static function isMappingValid(array $mapping, int $numberOfItems): bool
    {
        $usedCheck = array_fill(1, $numberOfItems, 0);

        foreach($mapping as $value) {
            if ($value < 1 || $value > $numberOfItems) {
                return false;
            }

            if (isset($usedCheck[$value])) {
                $usedCheck[$value] += 1;
            }
        }

        foreach($usedCheck as $value) {
            if (!$value) {
                return false;
            }
        }

        return array_sum($usedCheck) === $numberOfItems;
    }
}