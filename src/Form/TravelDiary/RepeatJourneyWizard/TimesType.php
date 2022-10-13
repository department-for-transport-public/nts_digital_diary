<?php

namespace App\Form\TravelDiary\RepeatJourneyWizard;

use App\Form\TravelDiary\JourneyWizard\TimesType as JourneyTimesType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TimesType extends JourneyTimesType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'validation_groups' => ['wizard.repeat-journey.journey-times'],
            'help' => 'repeat-journey.adjust-times.help',
        ]);
    }
}