<?php

namespace App\ListPage\Field;

interface SortableInterface
{
    public function sortable(): self;
    public function getSortable(): bool;
}