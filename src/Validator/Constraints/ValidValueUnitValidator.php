<?php

namespace App\Validator\Constraints;

use Ghost\GovUkFrontendBundle\Model\ValueUnitInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Validator\Constraints\PositiveOrZero;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class ValidValueUnitValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof ValidValueUnit) {
            throw new UnexpectedTypeException($constraint, ValidValueUnit::class);
        }

        if (!$value) {
            return;
        }

        if (!$value instanceof ValueUnitInterface) {
            throw new UnexpectedValueException($value, ValueUnitInterface::class);
        }

        $validator = $this->context->getValidator()->inContext($this->context);

        if (!$constraint->allowBlank || !$value->getIsBlank()) {
            $numericConstraintClass = $constraint->allowZero ? PositiveOrZero::class : Positive::class;
            $validators = [
                new NotBlank(['message' => "{$constraint->translationPrefix}.{$constraint->valueBlankTranslationKey}"]),
                new $numericConstraintClass(['message' => "{$constraint->translationPrefix}.{$constraint->valuePositiveTranslationKey}"]),
            ];
            if ($constraint->isDecimal) {
                $validators[] = new Decimal([
                    'precision' => $constraint->decimalPrecision,
                    'scale' => $constraint->decimalScale,
                    'translationPrefix' => "$constraint->translationPrefix.value",
                ]);
            }
            $validator->atPath('value')->validate($value->getValue(), $validators, ['Default']);

            $validator->atPath('unit')->validate($value->getUnit(), [
                new NotBlank(['message' => "{$constraint->translationPrefix}.{$constraint->unitBlankTranslationKey}"]),
            ], ['Default']);
        }
    }
}