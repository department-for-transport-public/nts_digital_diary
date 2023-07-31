<?php

namespace App\Serializer\ChangeSet;

use App\Entity\Journey\Journey;
use App\Utility\Comparator\Comparator;
use App\Utility\Comparator\Comparators\DateTimeComparator;

class JourneyChangeSetNormalizer extends AbstractChangeSetNormalizer
{
    public function __construct(protected Comparator $comparator)
    {}

    public function supportsNormalization($data, string $format = null, array $context = []): bool
    {
        return is_array($data) &&
            ($context[self::CHANGE_SET_ENTITY_KEY] ?? null) instanceof Journey;
    }

    public function normalize($object, string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        // Format date fields correctly
        foreach($object as $field => [$oldValue, $newValue]) {
            if (in_array($field, ['startTime', 'endTime'])) {
                // Due to measures elsewhere, we should never receive an identical oldValue/newValue for startTime or
                // endTime. However, for test purposes, and as an extra measure we use the comparator here to check that
                // the times are actually different.
                if ($this->comparator->areEqual($oldValue, $newValue, [DateTimeComparator::COMPARE_TIMES_ONLY])) {
                    unset($object[$field]);
                    continue;
                }

                $object[$field] = [
                    $oldValue instanceof \DateTime ? $oldValue->format('H:i') : $oldValue,
                    $newValue instanceof \DateTime ? $newValue->format('H:i') : $newValue,
                ];
            }
        }

        // Rename fields for history / API output
        $this->renameField($object, 'isStartHome', 'startIsHome');
        $this->renameField($object, 'isEndHome', 'endIsHome');

        $this->whitelistFields($object, [
            'startTime',
            'startLocation',
            'startIsHome',
            'endTime',
            'endLocation',
            'endIsHome',
            'purpose',
        ]);

        return $object;
    }
}