<?php

namespace App\Form\OnBoarding\HouseholdWizard;

use App\Entity\Household;
use App\Features;
use Ghost\GovUkFrontendBundle\Form\Type\DateType;
use Ghost\GovUkFrontendBundle\Form\Type\InputType;
use Ghost\GovUkFrontendBundle\Form\Type\NumberType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class HouseholdType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('addressNumber', NumberType::class, [
                'label' => "household.details.address-number.label",
                'help' => "household.details.address-number.help",
                'attr' => ['class' => 'govuk-input--width-3'],
                'label_attr' => ['class' => 'govuk-label--s'],
            ])
            ->add('householdNumber', NumberType::class, [
                'label' => "household.details.household-number.label",
                'label_attr' => ['class' => 'govuk-label--s'],
                'help' => "household.details.household-number.help",
                'attr' => ['class' => 'govuk-input--width-2'],
            ]);

        if (Features::isEnabled(Features::CHECK_LETTER)) {
            $builder
                ->add('checkLetter', InputType::class, [
                    'label' => "household.details.check-letter.label",
                    'label_attr' => ['class' => 'govuk-label--s'],
                    'help' => "household.details.check-letter.help",
                    'attr' => ['class' => 'govuk-input--width-2'],
                ]);
        }

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function(FormEvent $event) {
            $data = $event->getData();
            assert($data instanceof Household);

            $areaPeriod = $data->getAreaPeriod();

            $event->getForm()
                ->add('diaryWeekStartDate', DateType::class, [
                    'label' => "household.details.diary-start.label",
                    'label_attr' => ['class' => 'govuk-label--s'],
                    'help' => "household.details.diary-start.help",
                    'help_translation_parameters' => [
                        'start_date' => $areaPeriod->getFirstValidDiaryStartDate(),
                        'end_date' => $areaPeriod->getLastValidDiaryStartDate(),
                    ],
                ]);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $validationGroups = function (FormInterface $form) {
            /** @var Household $formData */
            $formData = $form->getData();
            $groups = ['wizard.on-boarding.household'];
            if (
                Features::isEnabled(Features::CHECK_LETTER)
                && !$formData->getAreaPeriod()->getTrainingInterviewer()
            ) {
                $groups[] = 'wizard.on-boarding.check-letter';
            }
            return $groups;
        };

        $resolver->setDefaults([
            'data_class' => Household::class,
            'translation_domain' => 'on-boarding',
            'validation_groups' => $validationGroups,
        ]);
    }
}
