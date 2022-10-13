<?php

namespace App\Validator\Constraints;

use Ghost\GovUkFrontendBundle\Form\DataTransformer\DecimalToStringTransformer;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Validator\Constraints\PositiveOrZero;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class DecimalValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof Decimal) {
            throw new UnexpectedTypeException($constraint, Decimal::class);
        }

        if (!$value) {
            return;
        }

        if (!preg_match(DecimalToStringTransformer::VALIDATION_REGEX, $value, $matches)) {
            throw new UnexpectedValueException($value, 'decimal woo');
        }

        $max = pow(10, $constraint->precision - $constraint->scale);
        if ($value > $max) {
            $this->context->buildViolation("$constraint->translationPrefix.$constraint->maxMessageKey")
                ->setParameter('max', $max)
                ->addViolation();
        }
        if (strlen($matches['dec'] ?? '') > $constraint->scale) {
            $this->context->buildViolation("$constraint->translationPrefix.$constraint->placesMessageKey")
                ->setParameter('scale', $constraint->scale)
                ->addViolation();
        }
    }
}