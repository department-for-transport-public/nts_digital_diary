<?php

namespace App\Validator\Constraints;

use App\Utility\CharacterTypeCounter;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class PasswordComplexityValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof PasswordComplexity) {
            throw new UnexpectedTypeException($constraint, PasswordComplexity::class);
        }

        if (null === $value) {
            return;
        }

        $types = CharacterTypeCounter::getCharacterTypeCounts($value);

        if ($types['upper'] < 1 || $types['lower'] < 1 || $types['number'] < 1 || mb_strlen($value) < 8) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}
