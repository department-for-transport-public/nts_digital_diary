<?php

namespace App\Form\OnBoarding\DirectOnboardingWizard;

use App\Form\OnBoarding\ConfirmHouseholdType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReviewHouseholdType extends ConfirmHouseholdType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'translation_domain' => 'on-boarding',
        ]);
    }

    public function getParent(): string
    {
        return FormType::class;
    }
}