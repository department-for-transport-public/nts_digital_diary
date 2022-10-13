<?php

namespace App\ListPage\Field;

use Doctrine\ORM\QueryBuilder;

class SpaceStrippingTextFilter extends TextFilter
{
    public function addFilterCondition(QueryBuilder $queryBuilder, $formData): QueryBuilder
    {
        $sansSpaces = str_replace(' ', '', $formData);
        return parent::addFilterCondition($queryBuilder, $sansSpaces);
    }
}