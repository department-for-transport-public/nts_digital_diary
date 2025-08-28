<?php

namespace App\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class Redirect
{
    public function __construct(
        protected string $expression,
        protected string $route,
        protected array  $routeParams = [],
    ) {}

    public function getExpression(): string
    {
        return $this->expression;
    }

    public function getRoute(): string
    {
        return $this->route;
    }

    public function getRouteParams(): array
    {
        return $this->routeParams;
    }
}
