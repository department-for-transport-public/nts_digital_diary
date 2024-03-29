<?php

namespace App\Entity\Embeddable;

use Brick\Math\BigDecimal;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;

/**
 * @ORM\Embeddable()
 */
class CostOrNil implements JsonSerializable
{
    /**
     * @ORM\Column(type="decimal_brick", precision=10, scale=2, nullable=true)
     */
    private ?BigDecimal $cost;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private ?bool $hasCost;

    public function __construct()
    {
        $this->cost = null;
        $this->hasCost = null;
    }

    public function getCost(): ?BigDecimal
    {
        return $this->cost ?? null;
    }

    public function setCost(?BigDecimal $cost): self
    {
        if ($this->cost === null || $cost === null || !$this->cost->isEqualTo($cost)) {
            $this->cost = $cost;
        }
        return $this;
    }

    public function getHasCost():?bool
    {
        return $this->hasCost;
    }

    public function setHasCost(?bool $hasCost): self
    {
        $this->hasCost = $hasCost;
        return $this;
    }

    /**
     * Pretty sure this is used for property change log
     */
    public function jsonSerialize(): ?string
    {
        return $this->encodeToSingleValue();
    }

    /**
     * used in both export and property change log
     * - When hasCost is null, we haven't asked for this cost
     * - When hasCost is true
     *   - if the cost is null, encode to empty string
     *   - if the cost is not empty, return the string encoded value
     * - When hasCost is false, encode value as "0.00"
     */
    public function encodeToSingleValue(): ?string
    {
        return match(true) {
            $this->hasCost === null => null,
            $this->hasCost === true => $this->cost === null ? "" : strval($this->cost->toScale(2)),
            $this->hasCost === false => '0.00'
        };
    }

    public function decodeFromSingleValue(?string $value): self
    {
        $this->cost = $value === null ? null : BigDecimal::of($value);
        $this->hasCost = $value !== '0' && $value !== '0.00';

        return $this;
    }
}