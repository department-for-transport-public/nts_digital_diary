<?php

namespace App\ApiPlatform\Doctrine\Orm\Filter;

use ApiPlatform\Api\IriConverterInterface;
use ApiPlatform\Doctrine\Common\Filter\SearchFilterInterface;
use ApiPlatform\Doctrine\Common\Filter\SearchFilterTrait;
use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

class MappedPropertySearchFilter extends AbstractFilter implements SearchFilterInterface
{
    use SearchFilterTrait;

    protected const SEPARATOR = "::";

    protected array $mappings;
    protected SearchFilter $searchFilter;

    public function __construct(ManagerRegistry $managerRegistry, IriConverterInterface $iriConverter, PropertyAccessorInterface $propertyAccessor = null, LoggerInterface $logger = null, array $properties = null, NameConverterInterface $nameConverter = null)
    {
        parent::__construct($managerRegistry, $logger, $properties, $nameConverter);

        $this->iriConverter = $iriConverter;
        $this->propertyAccessor = $propertyAccessor ?: PropertyAccess::createPropertyAccessor();

        $this->mappings = [];
        $searchProperties = [];

        foreach($properties ?? [] as $property => $filter) {
            $filterParts = explode(self::SEPARATOR, $filter);
            $this->mappings[$property] = $filterParts[0];
            $searchProperties[$filterParts[0]] = $filterParts[1];
        }

        $this->searchFilter = new SearchFilter($managerRegistry, $iriConverter, $propertyAccessor, $logger, $searchProperties, $nameConverter);
    }

    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, Operation $operation = null, array $context = [])
    {
        // Never gets called because apply() has been overridden...
    }

    public function apply(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, Operation $operation = null, array $context = [])
    {
        $searchContext = $context;
        $searchContext['filters'] = [];

        foreach($context['filters'] as $property => $value) {
            $mappedProperty = $this->mappings[$property] ?? $property;
            $searchContext['filters'][$mappedProperty] = $value;
        }

        $this->searchFilter->apply($queryBuilder, $queryNameGenerator, $resourceClass, $operation, $searchContext);
    }

    protected function getType(string $doctrineType): string
    {
        $reflClass = new \ReflectionClass($this->searchFilter);
        $getType = $reflClass->getMethod('getType');
        return $getType->invoke($this->searchFilter, $doctrineType);
    }

    protected function getIriConverter(): IriConverterInterface
    {
        return $this->iriConverter;
    }

    protected function getPropertyAccessor(): PropertyAccessorInterface
    {
        return $this->propertyAccessor;
    }
}