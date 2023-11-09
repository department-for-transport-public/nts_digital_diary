<?php

namespace App\Serializer;

use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

class TranslatableMessageNormalizer implements NormalizerInterface
{
    public function __construct(protected TranslatorInterface $translator){}

    /**
     * @param $object TranslatableMessage
     */
    public function normalize($object, string $format = null, array $context = []): string
    {
        return $object->trans($this->translator);
    }

    public function supportsNormalization($data, string $format = null): bool
    {
        return $data instanceof TranslatableMessage;
    }
}