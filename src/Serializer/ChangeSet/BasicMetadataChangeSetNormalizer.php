<?php

namespace App\Serializer\ChangeSet;

use App\Entity\BasicMetadata;

class BasicMetadataChangeSetNormalizer extends AbstractChangeSetNormalizer
{
    const DIRECTLY_RUN_BASIC_METADATA_NORMALIZER = 'property-change-set_directly-run-basic-metadata-normalizer';

    public function supportsNormalization($data, string $format = null, array $context = []): bool
    {
        return is_array($data) &&
            ($context[self::CHANGE_SET_ENTITY_KEY] ?? null) instanceof BasicMetadata &&
            ($context[self::DIRECTLY_RUN_BASIC_METADATA_NORMALIZER] ?? null) === true;
    }

    public function normalize($object, string $format = null, array $context = [])
    {
        // Remove some fields
        $this->removeFields($object, [
            'modifiedAt',
            'modifiedBy',
            'createdAt',
            'createdBy',
        ]);

        return $object;
    }
}