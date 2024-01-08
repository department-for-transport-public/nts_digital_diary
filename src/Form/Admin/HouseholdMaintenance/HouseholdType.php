<?php

namespace App\Form\Admin\HouseholdMaintenance;

use Ghost\GovUkFrontendBundle\Form\Type\ButtonType;
use Ghost\GovUkFrontendBundle\Form\Type\NumberType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotNull;

class HouseholdType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('area', NumberType::class, [
                'label' => "household-maintenance.choose-household.area.label",
                'attr' => ['class' => 'govuk-input--width-5'],
                'label_attr' => ['class' => 'govuk-label--s'],
                'constraints' => [new NotNull(['message' => 'household-maintenance.choose-household.area.not-null'])],
            ])
            ->add('addressNumber', NumberType::class, [
                'label' => "household-maintenance.choose-household.address-number.label",
                'attr' => ['class' => 'govuk-input--width-3'],
                'label_attr' => ['class' => 'govuk-label--s'],
                'constraints' => [new NotNull(['message' => 'household-maintenance.choose-household.address-number.not-null'])],
            ])
            ->add('householdNumber', NumberType::class, [
                'label' => "household-maintenance.choose-household.household-number.label",
                'label_attr' => ['class' => 'govuk-label--s'],
                'attr' => ['class' => 'govuk-input--width-2'],
                'constraints' => [new NotNull(['message' => 'household-maintenance.choose-household.household-number.not-null'])],
            ])
            ->add('search', ButtonType::class, [
                'label' => "household-maintenance.choose-household.search.label"
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'translation_domain' => 'admin',
        ]);
    }
}