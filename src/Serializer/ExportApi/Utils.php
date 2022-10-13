<?php


namespace App\Serializer\ExportApi;


use App\Serializer\ExportApi\v1\DiaryKeeperNormalizer;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class Utils
{
    protected static PropertyAccessor $propertyAccessor;

    public static function formatFloat(?int $property, $places = 2): ?string
    {
        if (is_null($property)) return null;
        return number_format($property / pow(10, $places), $places, '.', '');
    }

    public static function getNullOrProperty($object, string $property)
    {
        if (is_null($object)) return null;
        return self::getPropertyAccessor()->getValue($object, $property);
    }

    public static function getPropertyAccessor(): PropertyAccessor
    {
        if (!isset(self::$propertyAccessor)) {
            self::$propertyAccessor = PropertyAccess::createPropertyAccessor();
        }
        return self::$propertyAccessor;
    }

    public static function getHistoryForObject(array $context, object $object)
    {
        $history = $context[DiaryKeeperNormalizer::CHANGELOG_KEY][$object->getId()] ?? null;
        if ($history === null || (is_array($history) && empty($history))) {
            $history = new \stdClass();
        }

        return $history;
    }
}