<?php

namespace App\Entity;

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

    public static function miles(int $distance): Distance {
        return (new Distance())->setUnit(self::UNIT_MILES)->setValue($distance);
    }

    public static function metres(int $distance): Distance {
        return (new Distance())->setUnit(self::UNIT_METRES)->setValue($distance);
    }

    /**
     * @ORM\Column(type="decimal", precision=8, scale=2, nullable=true)
     */
    private ?string $value;

    /**
     * @ORM\Column(type="string", length=12, nullable=true)
     */
    private ?string $unit;

    public function __toString()
    {
        return "{$this->value} {$this->unit}";
    }

    public function getValue(): ?string
    {
        return $this->value ?? null;
    }

    public function getValueNormalized($unit)
    {
        if ($this->value === null) {
            return null;
        }
        if ($this->unit === $unit) {
            return $this->value;
        }
        switch ($unit) {
            case self::UNIT_METRES :
                return $this->value * self::UNIT_CONVERSION_FACTOR;
            case self::UNIT_MILES :
                return $this->value / self::UNIT_CONVERSION_FACTOR;
            default:
                throw new InvalidArgumentException('Unexpected unit type');
        }
    }

    public function setValue($value): self
    {
        $this->value = (string) $value;
        return $this;
    }

    public function getUnit(): ?string
    {
        return $this->unit ?? null;
    }

    public function setUnit(?string $unit): self
    {
        $this->unit = $unit;
        return $this;
    }

    public function getIsBlank(): bool
    {
        return !isset($this->value) || is_null($this->value);
    }
}
