<?php

namespace App\Serializer\ApiPlatform;

use App\Entity\IdTrait;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;

class EntityAsIdNormalizer implements ContextAwareNormalizerInterface
{
    public const CONTEXT_KEY = "entity_as_id";

    public function supportsNormalization($data, string $format = null, array $context = []): bool
    {
        return ($context[self::CONTEXT_KEY] ?? false) === true
            && is_object($data)
            && in_array(IdTrait::class, class_uses($data) ?? []);
    }

    /**
     * @param $object IdTrait
     */
    public function normalize($object, string $format = null, array $context = []): ?string
    {
        return $object->getId();
    }
}