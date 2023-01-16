<?php

namespace App\Serializer\ApiPlatform;

use ApiPlatform\Problem\Serializer\ConstraintViolationListNormalizer;
use App\Annotation\ApiViolationMap;
use ArrayObject;
use Doctrine\Common\Annotations\Reader;
use Exception;
use ReflectionException;
use Symfony\Component\Serializer\Exception\ExceptionInterface as ExceptionInterfaceAlias;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

class ConstraintViolationListMappingNormalizer implements NormalizerAwareInterface, ContextAwareNormalizerInterface
{
    protected const CONTEXT_KEY = 'constraint-violation-list-mapped';
    private Reader $annotationReader;

    use NormalizerAwareTrait;

    public function __construct(Reader $annotationReader)
    {
        $this->annotationReader = $annotationReader;
    }

    public function supportsNormalization($data, string $format = null, $context = []): bool
    {
        return ($context[self::CONTEXT_KEY] ?? false) === false
            && $data instanceof ConstraintViolationList
            && $format === ConstraintViolationListNormalizer::FORMAT;
    }

    /**
     * @param $object ConstraintViolationList
     * @throws Exception
     * @throws ReflectionException|ExceptionInterfaceAlias
     */
    public function normalize($object, string $format = null, array $context = []): float|int|bool|ArrayObject|array|string|null
    {
        /** @var ConstraintViolation $item */
        foreach ($object->getIterator() as $index=>$item) {
            $annotation = $this->annotationReader->getClassAnnotation(new \ReflectionClass($item->getRoot()), ApiViolationMap::class);
            $map = $annotation ? $annotation->getMap() : [];
            if ($newPath = ($map[$item->getPropertyPath()] ?? false)) {
                $reflectionProperty = new \ReflectionProperty(ConstraintViolation::class, 'propertyPath');
                $reflectionProperty->setAccessible(true);
                $reflectionProperty->setValue($item, $newPath);
            }
        }
        return $this->normalizer->normalize($object, $format, [self::CONTEXT_KEY => true]);
    }
}