<?php

namespace App\Form;

use App\Entity\CostOrNil;
use Brick\Math\BigDecimal;
use Symfony\Component\Form\Extension\Core\DataMapper\DataMapper;
use Symfony\Component\Form\FormInterface;
use Traversable;

class CostOrNilDataMapper extends DataMapper
{
    /**
     * @param CostOrNil $data
     * @param iterable | Traversable $forms
     */
    public function mapDataToForms($data, iterable $forms): void
    {
        $forms = iterator_to_array($forms);
        /** @var FormInterface[] $forms */

        $cost = $data?->getCost();
        $hasCost = $data?->getHasCost();

        if ($cost instanceof BigDecimal && $cost->isZero()) {
            $hasCost = false;
        }

        $forms[CostOrNilType::BOOLEAN_FIELD_NAME]->setData($hasCost);
        $forms[CostOrNilType::COST_FIELD_NAME]->setData($hasCost === true ? $cost : null);
    }

    /**
     * @param iterable | Traversable $forms
     * @param CostOrNil $data
     */
    public function mapFormsToData(iterable $forms, &$data): void
    {
        $forms = iterator_to_array($forms);
        /** @var FormInterface[] $forms */

        $hasCost = $forms[CostOrNilType::BOOLEAN_FIELD_NAME]->getData();
        $cost = $forms[CostOrNilType::COST_FIELD_NAME]->getData();

        if ($cost instanceof BigDecimal && $cost->isZero()) {
            $hasCost = false;
        }

        $data->setHasCost($hasCost);

        if ($hasCost === null) {
            $data->setCost(null);
        } else if ($hasCost === false) {
            $data->setCost(BigDecimal::zero()->toScale(2));
        } else if ($hasCost === true) {
            $data->setCost($cost);
        }
    }
}