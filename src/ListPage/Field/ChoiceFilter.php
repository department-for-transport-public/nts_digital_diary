<?php

namespace App\ListPage\Field;

use Doctrine\ORM\QueryBuilder;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;

class ChoiceFilter extends Simple implements FilterableInterface
{
    protected array $choices;
    private array $formOptions;

    public function __construct(string $label, string $propertyPath, array $choices, $formOptions = [], array $cellOptions = [])
    {
        parent::__construct($label, $propertyPath, $cellOptions);
        $this->choices = $choices;
        $this->formOptions = $formOptions;
    }

    public function getFormOptions(): array
    {
        return array_merge([
            'placeholder' => $this->getLabel(),
            'choices' => $this->choices,
            'expanded' => false,
        ], $this->formOptions);
    }

    public function getFormClass(): string
    {
        return Gds\ChoiceType::class;
    }

    public function addFilterCondition(QueryBuilder $queryBuilder, $formData): QueryBuilder
    {
        return $queryBuilder
            ->andWhere("{$this->getPropertyPath()} = :{$this->getId()}")
            ->setParameter($this->getId(), $formData);
    }
}