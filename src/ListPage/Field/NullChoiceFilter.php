<?php

namespace App\ListPage\Field;

use Doctrine\ORM\QueryBuilder;

class NullChoiceFilter extends ChoiceFilter
{
    // Same as a ChoiceFilter, except that false is encoded as NULL
    public function addFilterCondition(QueryBuilder $queryBuilder, $formData): QueryBuilder
    {
        return $formData === false ?
            $queryBuilder->andWhere("{$this->getPropertyPath()} IS NULL") :
            parent::addFilterCondition($queryBuilder, $formData);
    }
}