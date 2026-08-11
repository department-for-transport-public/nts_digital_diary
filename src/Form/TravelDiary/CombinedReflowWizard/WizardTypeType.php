<?php

namespace App\Form\TravelDiary\CombinedReflowWizard;

use App\FormWizard\TravelDiary\CombinedReflowState;
use App\Repository\DiaryDayRepository;
use Ghost\GovUkFrontendBundle\Form\Type\ChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WizardTypeType extends AbstractType
{
    public const array INJECT_TRANSLATION_PARAMETERS = [
        'dayNumber' => 'subject.diaryDay.number',
        'date' => 'journeyDayDateWithDOW',
    ];


    public function __construct(protected readonly DiaryDayRepository $diaryDayRepository) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $hasJourneys = $this->diaryDayRepository->getJourneyCountForUser() > 0;
        $builder
            ->add('wizardType', ChoiceType::class, [
                'label' => false, // must use a title in order to get the translation params we need at this stage
                'choices' => [
                    'add-journey.journey-type.choices.new.label' => CombinedReflowState::WIZARD_TYPE__ADD,
                    'add-journey.journey-type.choices.repeat.label' => CombinedReflowState::WIZARD_TYPE__REPEAT,
                    'add-journey.journey-type.choices.return.label' => CombinedReflowState::WIZARD_TYPE__RETURN,
                ],
                'choice_options' => [
                    'add-journey.journey-type.choices.new.label' => ['help' => 'add-journey.journey-type.choices.new.help'],
                    'add-journey.journey-type.choices.repeat.label' => [
                        'help' => 'add-journey.journey-type.choices.repeat.help',
                        'disabled' => $hasJourneys ? false : 'disabled'
                    ],
                    'add-journey.journey-type.choices.return.label' => [
                        'help' => 'add-journey.journey-type.choices.return.help',
                        'disabled' => $hasJourneys ? false : 'disabled'
                    ],
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'translation_domain' => 'travel-diary',
        ]);
    }
}