<?php

namespace App\ListPage\Admin\Field;

use App\Form\Admin\HouseholdMaintenance\HouseholdSerialFilterType;
use Doctrine\ORM\QueryBuilder;
use Ghost\GovUkCoreBundle\ListPage\Field\FilterableInterface;
use Ghost\GovUkCoreBundle\ListPage\Field\Simple;

class HouseholdSerialFilter extends Simple implements FilterableInterface
{
    public function getFormOptions(): array
    {
        return [];
    }

    public function getFormClass(): string
    {
        return HouseholdSerialFilterType::class;
    }

    public function addFilterCondition(QueryBuilder $queryBuilder, $formData): QueryBuilder
    {
        if (!isset($formData['area']) || !is_numeric($formData['area'])) {
            return $queryBuilder;
        }

        $expr[] = "areaPeriod.area = :area";
        $params['area'] = $formData['area'];

        if (isset($formData['addressNo']) && is_numeric($formData['addressNo'])) {
            $expr[] = "household.addressNumber = :add";
            $params['add'] = $formData['addressNo'];

            if (isset($formData['householdNo']) && is_numeric($formData['householdNo'])) {
                $expr[] = "household.householdNumber = :hh";
                $params['hh'] = $formData['householdNo'];
            }
        }

        $queryBuilder
            ->andWhere($queryBuilder->expr()->andX(...$expr));

        foreach($params as $key => $value) {
            $queryBuilder->setParameter($key, $value);
        }

        return $queryBuilder;
    }
}
