<?php

namespace App\Doctrine\DBAL\Types;

use Brick\Math\BigDecimal;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

class DecimalBrickType extends Type
{
    public function getName(): string
    {
        return 'decimal_brick';
    }

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getDecimalTypeDeclarationSQL($column);
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): ?BigDecimal
    {
        if ($value === null) {
            return null;
        }

        return BigDecimal::of($value);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value instanceof BigDecimal) {
            $value = strval($value);
        }

        return $value;
    }
}