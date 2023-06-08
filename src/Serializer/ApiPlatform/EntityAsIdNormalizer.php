<?php

namespace App\Serializer\ApiPlatform;

use App\ApiPlatform\ApiIdInterface;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;

class EntityAsIdNormalizer implements ContextAwareNormalizerInterface
{
    public const CONTEXT_KEY = "entity_as_id";

    public function supportsNormalization($data, string $format = null, array $context = []): bool
    {
        return ($context[self::CONTEXT_KEY] ?? false) === true
            && $data instanceof ApiIdInterface;
    }

    /**
     * @param $object ApiIdInterface
     */
    public function normalize($object, string $format = null, array $context = []): ?string
    {
        return $object->getApiId();
    }
}