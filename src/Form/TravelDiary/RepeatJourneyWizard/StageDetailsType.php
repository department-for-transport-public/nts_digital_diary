<?php


namespace App\Form\TravelDiary\RepeatJourneyWizard;


use App\Form\TravelDiary\AbstractStageDetailsType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StageDetailsType extends AbstractStageDetailsType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'validation_groups' => 'wizard.repeat-journey.stage-details',
        ]);
    }
}