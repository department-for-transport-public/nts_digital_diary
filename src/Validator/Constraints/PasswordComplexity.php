<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class PasswordComplexity extends Constraint
{
    public string $message = 'common.password.not-complex-enough';
}