<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\EmailValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class NoXssNonsenseValidator extends EmailValidator
{
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof NoXssNonsense) {
            throw new UnexpectedTypeException($constraint, NoXssNonsense::class);
        }

        // Strings get escaped correctly in various contexts, but still - there's
        // no need to be using these characters in a message in the first place
        if (str_contains((string) $value, '<') || str_contains((string) $value, '>')) {
            $this->context->addViolation($constraint->messageNoGtOrLt);
        } else if (str_contains((string) $value, 'javascript:')) {
            $this->context->addViolation($constraint->messageNoBadStrings);
        }
    }
}
