<?php

namespace App\ListPage\Field;

class Simple implements SortableInterface
{
    protected string $id;
    protected string $label;
    protected ?string $propertyPath;
    protected array $cellOptions;
    protected bool $sortable;

    public function __construct(string $label, ?string $propertyPath = null, array $cellOptions = [])
    {
        $this->id = self::generateId($label);

        $this->label = $label;
        $this->propertyPath = $propertyPath;
        $this->cellOptions = $cellOptions;

        $this->sortable = false;
    }

    public function sortable(): self {
        $this->sortable = true;
        return $this;
    }

    public static function generateId(string $label): string
    {
        $tmp = preg_replace('/[^a-zA-Z0-9\s]/', '', $label);
        $tmp = preg_replace('/\s+/', '_', $tmp);
        return strtolower($tmp);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getPropertyPath(): ?string
    {
        return $this->propertyPath;
    }

    public function getSortable(): bool
    {
        return $this->sortable;
    }

    public function getCellOptions(): array
    {
        return $this->cellOptions;
    }
}