<?php

namespace Ghost\GovUkFrontendBundle\Form\View;

use Symfony\Component\Form\ChoiceList\View\ChoiceGroupView as SymfonyChoiceGroupView;

class ChoiceGroupView extends SymfonyChoiceGroupView
{
    public ?array $labelAttr;
    public string $labelHeadingElement;

    public function __construct(SymfonyChoiceGroupView $original, $labelAttr = [], $labelHeadingElement = 'h1')
    {
        parent::__construct($original->label, $original->choices);
        $this->labelAttr = $labelAttr;
        $this->labelHeadingElement = $labelHeadingElement;
    }
}