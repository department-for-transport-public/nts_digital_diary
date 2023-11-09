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
        $searchValue = trim($formData);

        $cellOptions = $this->getCellOptions();
        if ($cellOptions['replace_slashes_with_dashes'] ?? false) {
            // This allows a search for dates to be more permissive in its format (i.e. 2023/09 works as well as 2023-09)
            $searchValue = str_replace(['\\', '/'], ['-', '-'], $searchValue);
        }

        return $queryBuilder
            ->andWhere("{$this->getPropertyPath()} LIKE :{$this->getId()}")
            ->setParameter($this->getId(), "%{$searchValue}%");
    }
}