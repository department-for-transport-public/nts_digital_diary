<?php

namespace App\Serializer\ApiPlatform;

use ApiPlatform\Serializer\ConstraintViolationListNormalizer;
use App\Attribute\ApiViolationMap;
use ArrayObject;
use Exception;
use ReflectionException;
use Symfony\Component\Serializer\Exception\ExceptionInterface as ExceptionInterfaceAlias;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

class ConstraintViolationListMappingNormalizer implements NormalizerAwareInterface, NormalizerInterface
{
    protected const CONTEXT_KEY = 'constraint-violation-list-mapped';

    use NormalizerAwareTrait;

    public function supportsNormalization($data, string $format = null, $context = []): bool
    {
        return ($context[self::CONTEXT_KEY] ?? false) === false
            && $data instanceof ConstraintViolationList
            && $format === ConstraintViolationListNormalizer::FORMAT;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            ConstraintViolationList::class => false, // false/not-cacheable because it depends upon the $context
        ];
    }

    /**
     * @param $object ConstraintViolationList
     * @throws Exception
     * @throws ReflectionException|ExceptionInterfaceAlias
     */
    public function normalize($object, string $format = null, array $context = []): float|int|bool|ArrayObject|array|string|null
    {
        /** @var ConstraintViolation $item */
        foreach ($object->getIterator() as $item) {
            $reflClass = new \ReflectionClass($item->getRoot());

            $attributes = $reflClass->getAttributes();
            $map = [];

            foreach($attributes as $attribute) {
                if ($attribute->getName() === ApiViolationMap::class) {
                    $map = $attribute->newInstance()->getMap();
                    break;
                }
            }

            if ($newPath = ($map[$item->getPropertyPath()] ?? false)) {
                $reflectionProperty = new \ReflectionProperty(ConstraintViolation::class, 'propertyPath');
                $reflectionProperty->setValue($item, $newPath);
            }
        }

        return $this->normalizer->normalize($object, $format, [self::CONTEXT_KEY => true]);
    }
}
