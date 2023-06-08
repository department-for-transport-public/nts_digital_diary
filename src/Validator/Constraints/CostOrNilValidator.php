<?php

namespace App\Validator\Constraints;

use App\Entity\CostOrNil as CostOrNilEntity;
use App\Form\CostOrNilType;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class CostOrNilValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
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

        if (!$constraint->allowBlank) {
            $validators = [
                new NotNull(['message' => "{$constraint->translationPrefix}.has-cost.not-null"]),
            ];
            $validator->atPath(CostOrNilType::BOOLEAN_FIELD_NAME)->validate($value->getHasCost(), $validators, ['Default']);
        }

        if ($value->getHasCost()) {
            $validators = [
                new NotNull(['message' => "{$constraint->translationPrefix}.cost.not-null"]),
                new Positive(['message' => "{$constraint->translationPrefix}.cost.positive"]),
            ];
            $validator->atPath(CostOrNilType::COST_FIELD_NAME)->validate($value->getCost(), $validators, ['Default']);
        }
    }
}