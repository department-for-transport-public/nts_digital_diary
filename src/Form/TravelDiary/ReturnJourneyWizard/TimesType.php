<?php

namespace App\Form\TravelDiary\ReturnJourneyWizard;

use App\Form\TravelDiary\JourneyWizard\TimesType as JourneyTimesType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TimesType extends JourneyTimesType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'validation_groups' => ['wizard.return-journey.journey-times'],
            'help' => 'return-journey.journey-times.help',
        ]);
    }
}