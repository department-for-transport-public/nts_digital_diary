<?php

namespace App\Serializer\ChangeSet;

use App\Entity\Journey\Journey;

class JourneyChangeSetNormalizer extends BasicMetadataChangeSetNormalizer
{
    public function supportsNormalization($data, string $format = null, array $context = []): bool
    {
        return is_array($data) &&
            ($context[self::CHANGE_SET_ENTITY_KEY] ?? null) instanceof Journey;
    }

    public function normalize($object, string $format = null, array $context = [])
    {
        $object = parent::normalize($object, $format, $context);

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

        // Remove some fields
        $this->removeFields($object, [
            'id',
            'diaryDay',
            'stages',
            'isPartial',
            'notifications',
        ]);

        return $object;
    }
}