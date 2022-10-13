<?php

namespace App\Validator\Constraints;

use App\Entity\User;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\EmailValidator;

class EmailOrNoLoginPlaceholderValidator extends EmailValidator
{
    public function validate($value, Constraint $constraint)
    {
        if (!User::isNoLoginPlaceholder($value)) {
            parent::validate($value, $constraint);
        }
    }
}