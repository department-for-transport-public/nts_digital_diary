<?php

namespace App\Annotation;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ConfigurationAnnotation;

/**
 * @Annotation
 *
 * @see \App\Serializer\ApiPlatform\ConstraintViolationListMappingNormalizer
 */
class ApiViolationMap extends ConfigurationAnnotation
{
    private array $map;

    public function setValue(array $map): void
    {
        $this->map = $map;
    }

    public function getMap(): array
    {
        return $this->map ?? [];
    }

    public function getAliasName(): string
    {
        return 'api-violation-map';
    }

    public function allowArray(): bool
    {
        return false;
    }
}