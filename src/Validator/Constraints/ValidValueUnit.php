<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class ValidValueUnit extends Constraint
{
    public function __construct(
        public ?string $translationPrefix,
        public ?string $unitBlankTranslationKey = "unit.not-blank",
        public ?string $valueBlankTranslationKey = "value.not-blank",
        public ?string $valuePositiveTranslationKey = "value.positive",
        public ?bool   $allowBlank = false,
        public ?bool   $allowZero = false,
        public ?bool   $isDecimal = false,
        public ?int    $decimalPrecision = 8,
        public ?int    $decimalScale = 1,
        ?array  $groups = null,
        mixed   $payload = null
    ) {
        parent::__construct([], $groups, $payload);
    }
}
