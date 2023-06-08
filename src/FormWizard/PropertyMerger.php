<?php

namespace App\FormWizard;

use App\Entity\IdTrait;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class PropertyMerger
{
    private EntityManagerInterface $entityManager;
    private PropertyAccessor $propertyAccess;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->propertyAccess = PropertyAccess::createPropertyAccessor();
    }

    /**
     * @template entity
     * @param entity|IdTrait $entity
     * @param object|IdTrait|null $overlayEntity
     * @return entity
     */
    public function merge(object $entity, ?object $overlayEntity, array $mergePropertyPaths = []): object
    {
        if (is_null($overlayEntity)) {
            return $entity;
        }
        if (get_class($entity) !== ClassUtils::getRealClass(get_class($overlayEntity))) {
            throw new RuntimeException('Entity and overlay are not of the same type');
        }
        if (!is_null($entity->getId()) && !is_null($overlayEntity->getId()) && $entity->getId() !== $overlayEntity->getId()) {
            throw new RuntimeException('Entity and overlay do not have the same $id');
        }

        foreach ($mergePropertyPaths as $mergePropertyPath) {
            $this->mergeProperty($entity, $overlayEntity, $mergePropertyPath);
        }
        return $entity;
    }

    /**
     * @template entity
     * @param entity|IdTrait $entity
     * @param entity|IdTrait|null $overlayEntity
     * @param string $collectionProperty
     * @param array $mergeCollectionPropertyPaths
     * @return entity
     */
    public function mergeCollection(object $entity, ?object $overlayEntity, string $collectionProperty, array $mergeCollectionPropertyPaths = []): object
    {
        $collection = $this->propertyAccess->getValue($overlayEntity, $collectionProperty);
        foreach ($collection as $key => $item) {
            $keyProperty = "{$collectionProperty}[$key]";
            $this->propertyAccess->setValue(
                $entity,
                $keyProperty,
                $this->merge($this->propertyAccess->getValue($entity, $keyProperty), $item, $mergeCollectionPropertyPaths)
            );
        }
        return $entity;
    }

    /**
     * @template entity
     * @param entity|IdTrait $entity
     * @param array $clonePropertyPaths
     * @return entity
     */
    public function clone(object $entity, array $clonePropertyPaths = []): object
    {
        $clone = new $entity();
        foreach ($clonePropertyPaths as $clonePropertyPath) {
            $this->mergeProperty($clone, $entity, $clonePropertyPath);
        }
        return $clone;
    }

    protected function mergeProperty(object $entity, object $overlayEntity, string $propertyPath, $refreshFromDb = true)
    {
        if (preg_match('/^\?/', $propertyPath)) {
            $propertyPath = preg_replace('/^\?/', '', $propertyPath);
            if (!$this->propertyAccess->isReadable($overlayEntity, $propertyPath)) {
                return;
            }
        }

        $overlayProperty = $this->propertyAccess->getValue($overlayEntity, $propertyPath);

        if ($overlayProperty instanceof Collection) {
            $entityProperty = $this->propertyAccess->getValue($entity, $propertyPath);
            $entityProperty->clear();
            foreach ($overlayProperty as $k=>$v) {
                $entityProperty->set($k, $refreshFromDb ? $this->checkAndReloadFromDatabase($v) : $v);
            }
            return;
        }

        if ($refreshFromDb) {
            $overlayProperty = $this->checkAndReloadFromDatabase($overlayProperty);
        }

        if ($overlayProperty instanceof \DateTimeInterface && $overlayProperty == $this->propertyAccess->getValue($entity, $propertyPath)) {
            return; // Skip replacing DateTimes with an equivalent value
        }

        if ($overlayProperty instanceof PropertyMergerNonEntityInterface) {
            $overlayProperty = $this->mergeNonEntity($this->propertyAccess->getValue($entity, $propertyPath), $overlayProperty);
        }

        $this->propertyAccess->setValue($entity, $propertyPath, $overlayProperty);
    }

    protected function mergeNonEntity(?PropertyMergerNonEntityInterface $target, PropertyMergerNonEntityInterface $source): PropertyMergerNonEntityInterface
    {
        $target  = $target ?? new $source();
        foreach ($source::getMergeProperties() as $mergePropertyPath) {
            $this->mergeProperty($target, $source, $mergePropertyPath);
        }
        return $target;
    }

    protected function checkAndReloadFromDatabase($value)
    {
        if (is_object($value)
            && $this->propertyAccess->isReadable($value, 'id')
            && ($overlayPropertyId = $this->propertyAccess->getValue($value, 'id'))
        ) {
            return $this->entityManager->getReference(ClassUtils::getRealClass(get_class($value)), $overlayPropertyId);
        }
        return $value;
    }
}