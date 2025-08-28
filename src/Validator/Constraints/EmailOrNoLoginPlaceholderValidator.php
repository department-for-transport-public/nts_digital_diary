<?php

namespace App\Validator\Constraints;

use App\Entity\User;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\EmailValidator;

class EmailOrNoLoginPlaceholderValidator extends EmailValidator
{
    public function __construct(string $defaultMode = Email::VALIDATION_MODE_HTML5)
    {
        parent::__construct($defaultMode);
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!User::isNoLoginPlaceholder($value)) {
            parent::validate($value, $constraint);
        }
    }
}