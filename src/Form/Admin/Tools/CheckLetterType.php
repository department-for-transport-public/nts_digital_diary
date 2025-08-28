<?php

namespace App\Form\Admin\Tools;

use App\Form\Admin\HouseholdMaintenance\HouseholdSerialFilterType;
use Ghost\GovUkFrontendBundle\Form\Type\ButtonType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CheckLetterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('serial', HouseholdSerialFilterType::class, [
                'label' => false,
                'attr' => [
                    'class' => 'govuk-fieldset--inline govuk-fieldset--inline-narrow',
                ],
            ])
            ->add('submit', ButtonType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'attr' => [
                'class' => 'govuk-fieldset--inline govuk-fieldset--inline-narrow',
            ],
        ]);
    }
}