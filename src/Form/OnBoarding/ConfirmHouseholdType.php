<?php

namespace App\Form\OnBoarding;

use App\Form\ConfirmActionType;
use Ghost\GovUkFrontendBundle\Form\Type\CheckboxType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class ConfirmHouseholdType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('isJourneySharingEnabled', CheckboxType::class, [
            'priority' => 100,
            'label' => 'submit.journey-sharing',
        ]);
    }

    public function getParent(): string
    {
        return ConfirmActionType::class;
    }
}