<?php

namespace App\Validator\Constraints;

use Brick\Math\BigDecimal;
use Brick\Math\Exception\MathException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class DecimalValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof Decimal) {
            throw new UnexpectedTypeException($constraint, Decimal::class);
        }

        if (!$value) {
            return;
        }

        try {
            $decimal = BigDecimal::of($value);
        } catch (MathException) {
            throw new UnexpectedValueException($value, 'Not a valid decimal');
        }

        $max = pow(10, $constraint->precision - $constraint->scale);
        if ($decimal->isGreaterThan($max)) {
            $this->context->buildViolation("$constraint->translationPrefix.$constraint->maxMessageKey")
                ->setParameter('max', $max)
                ->addViolation();
        }
        if ($decimal->getScale() > $constraint->scale) {
            $this->context->buildViolation("$constraint->translationPrefix.$constraint->placesMessageKey")
                ->setParameter('scale', $constraint->scale)
                ->addViolation();
        }
    }
}