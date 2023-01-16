<?php

namespace App\Serializer\ChangeSet;

use App\Entity\Journey\Journey;

class JourneyChangeSetNormalizer extends AbstractChangeSetNormalizer
{
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