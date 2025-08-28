<?php

namespace App\Form\OnBoarding\DirectOnboardingWizard\Ingress;

use App\Entity\DiaryKeeper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DiaryKeeperType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->setDataMapper(new DiaryKeeperDataMapper())

            ->add('name', TextType::class)
            ->add('adult', TextType::class, ['property_path' => 'isAdult'])
            ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DiaryKeeper::class,
            'error_bubbling' => false,
        ]);
    }
}