<?php

namespace App\ListPage\Field;

use Doctrine\ORM\QueryBuilder;

interface FilterableInterface
{
    public function getFormOptions(): array;
    public function getFormClass(): string;

    public function addFilterCondition(QueryBuilder $queryBuilder, $formData): QueryBuilder;
}