<?php

namespace App\Form\OnBoarding\DirectOnboardingWizard\Ingress;

use App\Entity\Vehicle;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VehicleType extends AbstractType
{
    public function __construct(protected VehicleDataMapper $ingressVehicleDataMapper) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->setDataMapper($this->ingressVehicleDataMapper)

            ->add('name', TextType::class)
            ->add('method', TextType::class)
            // Driver is mapped in the parent household data mapper, as it needs resolving to a DK
            ->add('driver', TextType::class)
            ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Vehicle::class,
            'error_bubbling' => false,
        ]);
    }
}