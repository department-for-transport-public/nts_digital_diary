<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class Decimal extends Constraint
{
    /**
     * The total number of digits allow, including decimal places
     */
    public int $precision = 8;

    /**
     * The number of decimal places
     */
    public int $scale = 1;

    public string $message = 'common.number.invalid';

    public string $translationPrefix;
    public string $maxMessageKey = 'too-big';
    public string $placesMessageKey = 'too-many-places';

    public function validatedBy(): string
    {
        return static::class.'Validator';
    }
}