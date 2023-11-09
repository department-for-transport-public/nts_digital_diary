<?php

namespace App\Entity\Embeddable;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Doctrine\ORM\Mapping as ORM;
use Ghost\GovUkFrontendBundle\Model\ValueUnitInterface;
use InvalidArgumentException;

/**
 * @ORM\Embeddable()
 */
class Distance implements ValueUnitInterface
{
    const UNIT_METRES = 'metres';
    const UNIT_MILES = 'miles';

    const UNIT_CONVERSION_FACTOR = 1609.344;

    const UNIT_TRANSLATION_PREFIX = 'unit.distance.';

    const UNIT_CHOICES = [
        self::UNIT_TRANSLATION_PREFIX . self::UNIT_MILES => self::UNIT_MILES,
        self::UNIT_TRANSLATION_PREFIX . self::UNIT_METRES => self::UNIT_METRES,
    ];

    /**
     * @ORM\Column(type="decimal_brick", precision=8, scale=2, nullable=true)
     */
    private ?BigDecimal $value;

    /**
     * @ORM\Column(type="string", length=12, nullable=true)
     */
    private ?string $unit;

    public function __construct()
    {
        $this->value = null;
        $this->unit = null;
    }

    public static function miles(string $distance): Distance {
        if (!is_numeric($distance)) {
            throw new InvalidArgumentException('$distance argument must be numeric');
        }

        return (new Distance())->setUnit(self::UNIT_MILES)->setValue(BigDecimal::of($distance)->toScale(2));
    }

    public static function metres(string $distance): Distance {
        if (!is_numeric($distance)) {
            throw new InvalidArgumentException('$distance argument must be numeric');
        }

        return (new Distance())->setUnit(self::UNIT_METRES)->setValue(BigDecimal::of($distance)->toScale(2));
    }

    public function __toString()
    {
        return "{$this->value} {$this->unit}";
    }

    public function getValue(): ?BigDecimal
    {
        return $this->value;
    }

    public function getValueNormalized($unit): ?BigDecimal
    {
        if ($this->value === null) {
            return null;
        }
        if ($this->unit === $unit) {
            return $this->value;
        }
        return match ($unit) {
            self::UNIT_METRES => $this->value->multipliedBy(self::UNIT_CONVERSION_FACTOR),
            self::UNIT_MILES => $this->value->dividedBy(self::UNIT_CONVERSION_FACTOR, null, RoundingMode::HALF_UP),
            default => throw new InvalidArgumentException('Unexpected unit type'),
        };
    }

    public function setValue(?BigDecimal $value): self
    {
        if ($this->value === null || $value === null || !$this->value->isEqualTo($value)) {
            $this->value = $value;
        }
        return $this;
    }

    public function getUnit(): ?string
    {
        return $this->unit;
    }

    public function setUnit(?string $unit): self
    {
        $this->unit = $unit;
        return $this;
    }

    public function getIsBlank(): bool
    {
        return $this->value === null;
    }
}
