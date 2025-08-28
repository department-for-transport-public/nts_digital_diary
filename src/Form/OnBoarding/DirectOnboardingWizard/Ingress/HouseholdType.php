<?php

namespace App\Form\OnBoarding\DirectOnboardingWizard\Ingress;

use App\Entity\Household;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class HouseholdType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->setDataMapper(new HouseholdDataMapper())

            ->add('add', TextType::class)
            ->add('h', TextType::class)
            ->add('cl', TextType::class, ['property_path' => 'checkLetter'])
            ->add('start', DateType::class, ['property_path' => 'diaryWeekStartDate', 'widget' => 'single_text', 'html5' => false])
            ->add('dk', CollectionType::class, [
                'entry_type' => DiaryKeeperType::class,
                'property_path' => 'diaryKeepers',
                'allow_add' => true,
            ])
            ->add('v', CollectionType::class, [
                'entry_type' => VehicleType::class,
                'property_path' => 'vehicles',
                'allow_add' => true,
            ])
            ->add('debug')

            ->add('submit', ButtonType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Household::class,
            'validation_groups' => ["direct-onboarding.ingress"],
            'csrf_protection' => false,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
