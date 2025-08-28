<?php

namespace App\Attribute;

/**
 * @see \App\Serializer\ApiPlatform\ConstraintViolationListMappingNormalizer
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class ApiViolationMap
{
    public function __construct(
        protected array $map,
    ) {}

    public function getMap(): array
    {
        return $this->map;
    }
}
