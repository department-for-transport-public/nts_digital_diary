<?php

namespace App\Form\TravelDiary\StageWizard;

use App\Entity\Journey\Stage;
use Ghost\GovUkFrontendBundle\Form\Type\BooleanChoiceType;
use Ghost\GovUkFrontendBundle\Form\Type\MoneyType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DriverAndParkingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $transPrefix = "stage.driver-and-parking";
        $builder
            ->add('isDriver', BooleanChoiceType::class, [
                'label' => "{$transPrefix}.driver-or-passenger.label",
                'label_attr' => ['class' => 'govuk-label--m'],
                'help' => "{$transPrefix}.driver-or-passenger.help",
                'attr' => [],
                'choices' => [
                    "{$transPrefix}.driver-or-passenger.choices.driver" => "true",
                    "{$transPrefix}.driver-or-passenger.choices.passenger" => "false",
                ],
                'choice_translation_domain' => $options['translation_domain'],
            ])
            ->add('parkingCost', MoneyType::class, [
                'label' => "{$transPrefix}.parking-cost.label",
                'label_attr' => ['class' => 'govuk-label--m'],
                'help' => "{$transPrefix}.parking-cost.help",
                'help_html' => 'markdown',
                'attr' => ['class' => 'govuk-input--width-5'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Stage::class,
            'translation_domain' => 'travel-diary',
            'validation_groups' => 'wizard.driver-and-parking',
        ]);
    }
}