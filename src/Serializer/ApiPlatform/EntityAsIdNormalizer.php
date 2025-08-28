<?php

namespace App\Serializer\ApiPlatform;

use App\ApiPlatform\ApiIdInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class EntityAsIdNormalizer implements NormalizerInterface
{
    public const CONTEXT_KEY = "entity_as_id";

    public function supportsNormalization($data, string $format = null, array $context = []): bool
    {
        return ($context[self::CONTEXT_KEY] ?? false) === true
            && $data instanceof ApiIdInterface;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            ApiIdInterface::class => false, // false/not-cacheable because it depends upon the $context
        ];
    }

    /**
     * @param $object ApiIdInterface
     */
    public function normalize($object, string $format = null, array $context = []): ?string
    {
        return $object->getApiId();
    }
}