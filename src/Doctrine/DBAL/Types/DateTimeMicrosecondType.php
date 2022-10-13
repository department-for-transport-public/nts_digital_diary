<?php

namespace App\Doctrine\DBAL\Types;

use DateTime;
use DateTimeInterface;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type;
use function date_create;

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

        if ($platform instanceof SqlitePlatform) {
            return "VARCHAR(24)";
        }

        throw Exception::notSupported(__METHOD__);
    }

    /**
     * {@inheritdoc}
     * @throws Exception If not supported on this platform.
     * @return mixed
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if ($value === null) {
            return $value;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format($this->getDateTimeFormatString($platform));
        }

        throw ConversionException::conversionFailedInvalidType($value, $this->getName(), ['null', 'DateTime']);
    }

    /**
     * {@inheritdoc}
     * @return mixed
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if ($value === null || $value instanceof DateTimeInterface) {
            return $value;
        }

        $val = DateTime::createFromFormat($this->getDateTimeFormatString($platform), $value);

        if ($val === false) {
            $val = date_create($value);
        }

        if ($val === false) {
            throw ConversionException::conversionFailedFormat(
                $value,
                $this->getName(),
                $platform->getDateTimeFormatString()
            );
        }

        return $val;
    }

    protected function getDateTimeFormatString(AbstractPlatform $platform): string
    {
        return $platform->getDateTimeFormatString() . '.u';
    }
}
