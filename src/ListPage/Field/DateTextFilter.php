<?php

namespace App\ListPage\Field;

use Doctrine\ORM\QueryBuilder;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;

class DateTextFilter extends Simple implements FilterableInterface
{
    protected array $formOptions;

    public function __construct(string $label, string $propertyPath, $formOptions = [], array $cellOptions = [])
    {
        parent::__construct($label, $propertyPath, $cellOptions);
        $this->formOptions = $formOptions;
    }

    public function getFormOptions(): array
    {
        return $this->formOptions;
    }

    public function getFormClass(): string
    {
        return Gds\InputType::class;
    }

    public function addFilterCondition(QueryBuilder $queryBuilder, $formData): QueryBuilder
    {
        $normalisedDate = $this->normaliseDate(trim($formData));

        return $queryBuilder
            ->andWhere("{$this->getPropertyPath()} LIKE :{$this->getId()}")
            ->setParameter($this->getId(), "%{$normalisedDate}%");
    }

    protected function normaliseDate(string $dateString): string
    {
        $dateStringWithDashes = str_replace(['\\', '/'], ['-', '-'], $dateString);

        $defaults = [
            'partsOrder' => null,
            'isValid' => false,
        ];

        $day = '0\d|1\d|2\d|30|31';
        $month = '0\d|10|11|12';

        $formats = [
            [
                'regex' => '/^(\d{4})-('.$month.')-('.$day.')$/',
                'isValid' => true,
            ],
            [
                'regex' => '/^(\d{4})-('.$month.')$/',
                'isValid' => true,
            ],
            [
                'regex' => '/^(\d{4})$/',
                'isValid' => true,
            ],
            [
                'regex' => '/^('.$day.')-('.$month.')-(\d{4})$/',
                'partsOrder' => [2, 1, 0],
            ],
            [
                'regex' => '/^('.$month.')-(\d{4})$/',
                'partsOrder' => [1, 0],
            ],
        ];

        foreach($formats as $format) {
            [
                'regex' => $regex,
                'partsOrder' => $partsOrder,
                'isValid' => $isValid,
            ] = array_merge($defaults, $format);

            if (preg_match($regex, $dateStringWithDashes)) {
                if ($isValid) {
                    return $dateStringWithDashes;
                }

                $parts = explode('-', $dateStringWithDashes);
                $outputParts = array_map(
                    fn(int $order) => $parts[$order],
                    $partsOrder
                );

                return join('-', $outputParts);
            }
        }

        return $dateStringWithDashes;
    }
}