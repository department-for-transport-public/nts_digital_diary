<?php

namespace App\Form\TravelDiary\RepeatJourneyWizard;

use App\Form\TravelDiary\JourneyWizard\PurposeType as JourneyPurposeType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PurposeType extends JourneyPurposeType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'purpose_label' => 'repeat-journey.purpose.label',
        ]);
    }
}