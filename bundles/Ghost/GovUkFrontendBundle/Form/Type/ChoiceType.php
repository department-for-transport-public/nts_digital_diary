<?php

namespace Ghost\GovUkFrontendBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType as ExtendedChoiceType;

class ChoiceType extends AbstractType
{
    public function getParent(): string
    {
        return ExtendedChoiceType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'gds_choice';
    }
}
