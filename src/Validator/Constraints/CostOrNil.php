<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class CostOrNil extends Constraint
{
    public string $translationPrefix;

    // Allow the radio/hasCost to be completely unselected?
    public bool $allowBlankHasCost = false;

    // When the radio/hasCost is true, Allow the cost input to be empty?
    public bool $allowBlankCost = false;
    public int $precision = 10;
    public int $scale = 2;

    public function validatedBy(): string
    {
        return static::class.'Validator';
    }
}