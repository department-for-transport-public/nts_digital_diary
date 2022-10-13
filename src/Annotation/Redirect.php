<?php

namespace App\Annotation;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ConfigurationAnnotation;

/**
 * @Annotation
 */
class Redirect extends ConfigurationAnnotation
{
    /**
     * The expression evaluated to determine if a redirect should take place.
     */
    private string $expression;

    /**
     * The name of the route to redirect to if $expression evaluates as true
     */
    private string $route;

    /**
     * The parameters for the route when redirecting
     */
    private array $routeParams = [];

    public function getExpression(): string
    {
        return $this->expression;
    }

    public function setExpression($expression)
    {
        $this->expression = $expression;
    }

    public function getRoute(): string
    {
        return $this->route;
    }

    public function setRoute(string $route): void
    {
        $this->route = $route;
    }

    public function getRouteParams(): array
    {
        return $this->routeParams;
    }

    public function setRouteParams(array $routeParams): void
    {
        $this->routeParams = $routeParams;
    }

    public function setValue($expression)
    {
        $this->setExpression($expression);
    }

    public function getAliasName(): string
    {
        return 'redirect';
    }

    public function allowArray(): bool
    {
        return true;
    }
}