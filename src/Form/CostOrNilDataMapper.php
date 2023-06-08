<?php

namespace App\Form;

use App\Entity\CostOrNil;
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
        switch(true) {
            case $cost > 0 : $forms[CostOrNilType::COST_FIELD_NAME]->setData($cost); break;
            default: $forms[CostOrNilType::COST_FIELD_NAME]->setData(null); break;
        }
        $forms[CostOrNilType::BOOLEAN_FIELD_NAME]->setData($data?->getHasCost());
    }

    /**
     * @param iterable | Traversable $forms
     * @param CostOrNil $data
     */
    public function mapFormsToData(iterable $forms, &$data): void
    {
        $forms = iterator_to_array($forms);
        /** @var FormInterface[] $forms */
        $data->setHasCost($hasPaid = $forms[CostOrNilType::BOOLEAN_FIELD_NAME]->getData());
        switch(true) {
            case is_null($hasPaid) : $data->setCost(null); break;
            case $hasPaid === true : $data->setCost($forms[CostOrNilType::COST_FIELD_NAME]->getData()); break;
            default : $data->setCost(0); break;
        }
    }
}