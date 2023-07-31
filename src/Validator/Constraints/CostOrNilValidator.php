<?php

namespace App\Validator\Constraints;

use App\Entity\CostOrNil as CostOrNilEntity;
use App\Form\CostOrNilType;
use Brick\Math\BigDecimal;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class CostOrNilValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof CostOrNil) {
            throw new UnexpectedTypeException($constraint, CostOrNil::class);
        }

        if (!$value) {
            return;
        }

        if (!$value instanceof CostOrNilEntity) {
            throw new UnexpectedValueException($value, CostOrNilEntity::class);
        }

        $validator = $this->context->getValidator()->inContext($this->context);

        if (!$constraint->allowBlankHasCost) {
            $validators = [
                new NotNull(['message' => "{$constraint->translationPrefix}.has-cost.not-null"]),
            ];
            $validator->atPath(CostOrNilType::BOOLEAN_FIELD_NAME)->validate($value->getHasCost(), $validators, ['Default']);
        }

        if ($value->getHasCost()) {
            $cost = $value->getCost();

            if ($cost instanceof BigDecimal && $cost->isNegative()) {
                $this->context
                    ->buildViolation("{$constraint->translationPrefix}.cost.positive")
                    ->atPath(CostOrNilType::COST_FIELD_NAME)
                    ->addViolation();
            } else {
                $validators = $constraint->allowBlankCost ?
                    [] :
                    [new NotNull(['message' => "{$constraint->translationPrefix}.cost.not-null"])];

                $validators[] = new Decimal([
                    'precision' => $constraint->precision,
                    'scale' => $constraint->scale,
                    'translationPrefix' => "{$constraint->translationPrefix}.cost",
                ]);
                $validator->atPath(CostOrNilType::COST_FIELD_NAME)->validate($value->getCost(), $validators, ['Default']);
            }
        }
    }
}