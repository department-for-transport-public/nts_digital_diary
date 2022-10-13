<?php

namespace Ghost\GovUkFrontendBundle\Twig;

use Symfony\Component\Form\FormView;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ConditionalHideExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('namesToIds', [$this, 'namesToIds']),
        ];
    }

    public function namesToIds(FormView $view, ?array $names): ?array
    {
        if ($names === null || empty($names)) {
            return null;
        }

        $root = $this->getRoot($view);

        $targets = array_filter(
            array_map(fn($n) => $root[$n] ?? null, $names),
            fn($a) => $a !== null
        );

        return array_map(fn($x) => $x->vars['id'], $targets);
    }

    protected function getRoot(FormView $view): FormView
    {
        while($view->parent) {
            $view = $view->parent;
        }

        return $view;
    }
}