<?php


namespace App\Utility;


use Symfony\Component\PropertyAccess\PropertyAccess;

class PropertyAccessHelper
{
    static function resolveMap($data, array $propertyMap = []): array
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        return array_map(
            function($propertyPath) use ($data, $propertyAccessor) {
                if (is_array($propertyPath)) {
                    return self::resolveMap($data, $propertyPath);
                } else if (is_string($propertyPath) && $propertyAccessor->isReadable($data, $propertyPath)) {
                    return $propertyAccessor->getValue($data, $propertyPath);
                }
                return $propertyPath;
            },
            $propertyMap
        );
    }
}