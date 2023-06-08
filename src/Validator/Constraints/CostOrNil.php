<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class CostOrNil extends Constraint
{
    public string $translationPrefix;
    public bool $allowBlank = false;

    public function validatedBy(): string
    {
        return static::class.'Validator';
    }
}