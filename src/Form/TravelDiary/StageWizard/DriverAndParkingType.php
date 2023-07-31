<?php

namespace App\Form\TravelDiary\StageWizard;

use App\Entity\Journey\Stage;
use Ghost\GovUkFrontendBundle\Form\Type\BooleanChoiceType;
use App\Form\CostOrNilType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DriverAndParkingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('isDriver', BooleanChoiceType::class, [
                'label' => "stage.driver-and-parking.driver-or-passenger.label",
                'label_attr' => ['class' => 'govuk-label--m'],
                'help' => "stage.driver-and-parking.driver-or-passenger.help",
                'attr' => [],
                'choices' => [
                    "stage.driver-and-parking.driver-or-passenger.choices.driver" => "true",
                    "stage.driver-and-parking.driver-or-passenger.choices.passenger" => "false",
                ],
                'choice_translation_domain' => $options['translation_domain'],
            ])
            ->add('parkingCost', CostOrNilType::class, [
                'translation_prefix' => "stage.driver-and-parking.parking-cost",
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Stage::class,
            'translation_domain' => 'travel-diary',
            'validation_groups' => 'wizard.stage.driver-and-parking',
        ]);
    }
}