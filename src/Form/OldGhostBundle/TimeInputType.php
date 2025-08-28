<?php

namespace App\Form\OldGhostBundle;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TimeInputType extends AbstractType
{
    public function getParent(): string
    {
        return NumberType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'row_attr' => ['class' => 'govuk-time-input__item'],
            'label_attr' => ['class' => 'govuk-time-input__label'],
            'attr' => [
                'class' => 'govuk-time-input__input govuk-input--width-2',
            ],
        ]);
    }
}
