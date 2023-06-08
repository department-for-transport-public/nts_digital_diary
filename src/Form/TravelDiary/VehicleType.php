<?php

namespace App\Form\TravelDiary;

use App\Entity\Vehicle;
use Ghost\GovUkFrontendBundle\Form\Type\ButtonGroupType;
use Ghost\GovUkFrontendBundle\Form\Type\ButtonType;
use Ghost\GovUkFrontendBundle\Form\Type\ChoiceType;
use Ghost\GovUkFrontendBundle\Form\Type\LinkType;
use Ghost\GovUkFrontendBundle\Form\Type\NumberType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;

class VehicleType extends AbstractType
{
    private RouterInterface $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('odometerUnit', ChoiceType::class, [
                'label' => "vehicle.odometer-readings.unit.label",
                'help' => "vehicle.odometer-readings.unit.help",
                'label_attr' => ['class' => 'govuk-label--m'],
                'choices' => [
                    'unit.odometer-reading.miles' => Vehicle::ODOMETER_UNIT_MILES,
                    'unit.odometer-reading.kilometres' => Vehicle::ODOMETER_UNIT_KILOMETERS,
                ],
                'choice_translation_domain' => 'messages',
            ])
            ->add('weekStartOdometerReading', NumberType::class, [
                'label' => "vehicle.odometer-readings.week-start-reading.label",
                'help' => "vehicle.odometer-readings.week-start-reading.help",
                'label_attr' => ['class' => 'govuk-label--m'],
                'attr' => ['class' => 'govuk-input--width-5'],
            ])
            ->add('weekEndOdometerReading', NumberType::class, [
                'label' => "vehicle.odometer-readings.week-end-reading.label",
                'help' => "vehicle.odometer-readings.week-end-reading.help",
                'label_attr' => ['class' => 'govuk-label--m'],
                'attr' => ['class' => 'govuk-input--width-5'],
            ])
            ->add('button_group', ButtonGroupType::class);
        ;
        $builder
            ->get('button_group')
            ->add('save', ButtonType::class, [
                'label' => "actions.save",
                'translation_domain' => 'messages',
            ])
            ->add('cancel', LinkType::class, [
                'label' => 'actions.cancel',
                'translation_domain' => 'messages',
                'href' => $this->router->generate('traveldiary_dashboard'),
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Vehicle::class,
            'translation_domain' => 'travel-diary',
            'validation_groups' => 'vehicle.odometer-readings',
        ]);
    }
}
