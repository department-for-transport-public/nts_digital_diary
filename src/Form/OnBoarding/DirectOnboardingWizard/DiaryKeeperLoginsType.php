<?php

namespace App\Form\OnBoarding\DirectOnboardingWizard;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DiaryKeeperLoginsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('diaryKeepers', CollectionType::class, [
                'label' => false,
                'entry_type' => MediaAndIdentityType::class,
                'entry_options' => [
                    'label' => false,
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'translation_domain' => 'on-boarding',
            'validation_groups' => ['direct-onboarding.logins'],
        ]);
    }
}
