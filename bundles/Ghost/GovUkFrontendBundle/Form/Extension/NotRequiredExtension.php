<?php

namespace Ghost\GovUkFrontendBundle\Form\Extension;

use Ghost\GovUkFrontendBundle\Form\Type\CheckboxType;
use Ghost\GovUkFrontendBundle\Form\Type\ChoiceType;
use Ghost\GovUkFrontendBundle\Form\Type\TextareaType;
use Ghost\GovUkFrontendBundle\Form\Type\InputType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NotRequiredExtension extends AbstractTypeExtension
{
    public static function getExtendedTypes(): iterable
    {
        return [
            InputType::class,
            TextareaType::class,
            ChoiceType::class,
            CheckboxType::class,
        ];
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'required' => false,
        ]);
    }
}