<?php

namespace Ghost\GovUkFrontendBundle\Form\Type;

use Symfony\Bridge\Doctrine\Form\Type\EntityType as ExtendedEntityType;
use Symfony\Component\Form\AbstractType;

class EntityType extends AbstractType
{
    public function getBlockPrefix(): string
    {
        return 'gds_choice';
    }

    public function getParent(): string
    {
        return ExtendedEntityType::class;
    }
}
