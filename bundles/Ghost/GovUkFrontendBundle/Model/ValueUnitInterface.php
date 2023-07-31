<?php

namespace Ghost\GovUkFrontendBundle\Model;

use Brick\Math\BigDecimal;

interface ValueUnitInterface
{
    public function getValue(): ?BigDecimal;
    public function setValue(?BigDecimal $value);
    public function getUnit() : ?string;
    public function setUnit(?string $unit);
    public function getIsBlank(): bool;
}