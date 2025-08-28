<?php

namespace App\Form\Admin\HouseholdMaintenance;

use Ghost\GovUkFrontendBundle\Form\Type\InputType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class HouseholdSerialFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('area', InputType::class, [
                'attr' => [
                    'placeholder' => 'Area',
                    'class' => 'govuk-input--width-4',
                ],
                'label' => false,
            ])
            ->add('addressNo', InputType::class, [
                'attr' => [
                    'placeholder' => 'A',
                    'class' => 'govuk-input--width-2',
                ],
                'label' => false,
            ])
            ->add('householdNo', InputType::class, [
                'attr' => [
                    'placeholder' => 'H',
                    'class' => 'govuk-input--width-2',
                ],
                'label' => false,
            ])
            ;
    }
}