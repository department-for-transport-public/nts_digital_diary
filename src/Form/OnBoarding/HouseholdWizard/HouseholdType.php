<?php

namespace App\Form\OnBoarding\HouseholdWizard;

use App\Entity\Household;
use App\Features;
use Ghost\GovUkFrontendBundle\Form\Type\DateType;
use Ghost\GovUkFrontendBundle\Form\Type\InputType;
use Ghost\GovUkFrontendBundle\Form\Type\NumberType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class HouseholdType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('addressNumber', NumberType::class, [
                'label' => "household.details.address-number.label",
                'help' => "household.details.address-number.help",
                'attr' => ['class' => 'govuk-input--width-5'],
                'label_attr' => ['class' => 'govuk-label--s'],
            ])
            ->add('householdNumber', NumberType::class, [
                'label' => "household.details.household-number.label",
                'label_attr' => ['class' => 'govuk-label--s'],
                'help' => "household.details.household-number.help",
                'attr' => ['class' => 'govuk-input--width-5'],
            ]);

        if (Features::isEnabled(Features::CHECK_LETTER)) {
            $builder
                ->add('checkLetter', InputType::class, [
                    'label' => "household.details.check-letter.label",
                    'label_attr' => ['class' => 'govuk-label--s'],
                    'help' => "household.details.check-letter.help",
                    'attr' => ['class' => 'govuk-input--width-3'],
                ]);
        }

        $builder
            ->add('diaryWeekStartDate', DateType::class, [
                'label' => "household.details.diary-start.label",
                'label_attr' => ['class' => 'govuk-label--s'],
                'help' => "household.details.diary-start.help",
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $validationGroups = ['wizard.on-boarding.household'];

        if (Features::isEnabled(Features::CHECK_LETTER)) {
            $validationGroups[] = 'wizard.on-boarding.check-letter';
        }

        $resolver->setDefaults([
            'data_class' => Household::class,
            'translation_domain' => 'on-boarding',
            'validation_groups' => $validationGroups,
        ]);
    }
}
