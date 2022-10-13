<?php

namespace App\ListPage\Field;

use Doctrine\ORM\QueryBuilder;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;

class TextFilter extends Simple implements FilterableInterface
{
    protected array $formOptions;

    public function __construct(string $label, string $propertyPath, $formOptions = [], array $cellOptions = [])
    {
        parent::__construct($label, $propertyPath, $cellOptions);
        $this->formOptions = $formOptions;
    }

    public function getFormOptions(): array
    {
        return array_merge([
            // no default options
        ], $this->formOptions);
    }

    public function getFormClass(): string
    {
        return Gds\InputType::class;
    }

    public function addFilterCondition(QueryBuilder $queryBuilder, $formData): QueryBuilder
    {
        $trimmed = trim($formData);
        return $queryBuilder
            ->andWhere("{$this->getPropertyPath()} LIKE :{$this->getId()}")
            ->setParameter($this->getId(), "%{$trimmed}%");
    }
}