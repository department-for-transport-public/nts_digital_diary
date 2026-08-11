<?php

namespace App\Form\Admin\HouseholdMaintenance;

use Ghost\GovUkFrontendBundle\Form\Type\InputType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class HouseholdSerialFilterType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'translation_domain' => 'admin',
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('area', InputType::class, [
                'attr' => [
                    'placeholder' => 'tools.index.check-letter.area',
                    'class' => 'govuk-input--width-4',
                ],
                'label' => false,
            ])
            ->add('addressNo', InputType::class, [
                'attr' => [
                    'placeholder' => 'tools.index.check-letter.address-number',
                    'class' => 'govuk-input--width-2',
                ],
                'label' => false,
            ])
            ->add('householdNo', InputType::class, [
                'attr' => [
                    'placeholder' => 'tools.index.check-letter.household-number',
                    'class' => 'govuk-input--width-2',
                ],
                'label' => false,
            ])
            ;
    }
}