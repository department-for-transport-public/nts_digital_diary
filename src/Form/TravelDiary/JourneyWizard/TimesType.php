<?php

namespace App\Form\TravelDiary\JourneyWizard;

use App\Entity\Journey\Journey;
use App\Form\OldGhostBundle\TimeType;
use App\Utility\TravelDiary\UserPreferencesHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TimesType extends AbstractType
{
    public function __construct(protected UserPreferencesHelper $userPreferencesHelper) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('startTime', TimeType::class, [
                'label' => "journey.times.start.label",
                'label_attr' => ['class' => 'govuk-fieldset__legend--m'],
                'mode' => $this->userPreferencesHelper->getOriginalUserPreferences()->getTimeFormat(),
                'limit_input_chars' => true,
            ])
            ->add('endTime', TimeType::class, [
                'label' => "journey.times.end.label",
                'label_attr' => ['class' => 'govuk-fieldset__legend--m'],
                'mode' => $this->userPreferencesHelper->getOriginalUserPreferences()->getTimeFormat(),
                'limit_input_chars' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Journey::class,
            'translation_domain' => 'travel-diary',
            'validation_groups' => ['wizard.journey.times'],
        ]);
    }
}
