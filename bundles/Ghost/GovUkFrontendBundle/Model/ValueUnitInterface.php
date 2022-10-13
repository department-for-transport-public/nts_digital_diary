<?php


namespace Ghost\GovUkFrontendBundle\Model;


interface ValueUnitInterface
{
    public function getValue();
    public function setValue($value);
    public function getUnit() : ?string;
    public function setUnit(?string $unit);
    public function getIsBlank(): bool;
}