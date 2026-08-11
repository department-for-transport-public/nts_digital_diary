<?php

namespace App\Doctrine\DBAL\Types;

use DateTime;
use DateTimeInterface;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\Exception\NotSupported;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\DBAL\Types\Exception\InvalidFormat;
use Doctrine\DBAL\Types\Exception\InvalidType;
use Doctrine\DBAL\Types\Type;

/**
 * Type that maps an SQL DATETIME/TIMESTAMP to a PHP DateTime object.
 */
class DateTimeMicrosecondType extends Type
{
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'datetimemicrosecond';
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     * @throws Exception If not supported on this platform.
     */
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        if ($platform instanceof MySQLPlatform) {
            return "DATETIME(6)";
        }

        if ($platform instanceof SQLitePlatform) {
            return "VARCHAR(24)";
        }

        throw NotSupported::new(__METHOD__);
    }

    /**
     * {@inheritdoc}
     * @throws Exception If not supported on this platform.
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform): mixed
    {
        if ($value === null) {
            return $value;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format($this->getDateTimeFormatString($platform));
        }

        throw InvalidType::new(
            $value,
            static::class,
            ['null', DateTime::class],
        );
    }

    /**
     * {@inheritdoc}
     */
    public function convertToPHPValue($value, AbstractPlatform $platform): null|DateTimeInterface|DateTime
    {
        if ($value === null || $value instanceof DateTimeInterface) {
            return $value;
        }

        $val = DateTime::createFromFormat($this->getDateTimeFormatString($platform), $value);

        if ($val === false) {
            $val = date_create($value);
        }

        if ($val === false) {
            throw InvalidFormat::new(
                $value,
                static::class,
                $platform->getDateTimeFormatString(),
            );
        }

        return $val;
    }

    protected function getDateTimeFormatString(AbstractPlatform $platform): string
    {
        return $platform->getDateTimeFormatString() . '.u';
    }
}
