<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 * @Target({"PROPERTY"})
 */
class ChangeUserEmailAddress extends Constraint
{
    public string $message = 'common.email.already-used';
    public ?string $userId = null;
}