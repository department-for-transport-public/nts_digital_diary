<?php

namespace App\Form;

use App\Entity\Embeddable\CostOrNil;
use Brick\Math\BigDecimal;
use Symfony\Component\Form\Extension\Core\DataMapper\DataMapper;
use Symfony\Component\Form\FormInterface;

class CostOrNilDataMapper extends DataMapper
{
    /**
     * @param CostOrNil $data
     * @param \Traversable<FormInterface> $forms
     */
    public function mapDataToForms($data, \Traversable $forms): void
    {
        $forms = iterator_to_array($forms);

        $cost = $data?->getCost();
        $hasCost = $data?->getHasCost();

        if ($cost instanceof BigDecimal && $cost->isZero()) {
            $hasCost = false;
        }

        $forms[CostOrNilType::BOOLEAN_FIELD_NAME]->setData($hasCost);
        $forms[CostOrNilType::COST_FIELD_NAME]->setData($hasCost === true ? $cost : null);
    }

    /**
     * @param \Traversable<FormInterface> $forms
     * @param CostOrNil $data
     */
    public function mapFormsToData(\Traversable $forms, &$data): void
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