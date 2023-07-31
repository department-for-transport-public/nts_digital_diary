<?php

namespace App\Form\TravelDiary\SplitJourneyWizard;

use App\Form\TravelDiary\AbstractLocationType;
use App\FormWizard\TravelDiary\SplitJourneySubject;
use App\Repository\DiaryKeeperRepository;
use Symfony\Component\Form\Event\PreSetDataEvent;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Validator\Constraints\NotNull;

class MidpointType extends AbstractLocationType
{
    public function __construct(
        DiaryKeeperRepository $diaryKeeperRepository,
        Security $security,
        protected MidpointDataMapper $midpointDataMapper
    ) {
        parent::__construct($diaryKeeperRepository, $security);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->setDataMapper($this->midpointDataMapper);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function(PreSetDataEvent $event) use ($options) {
            /** @var SplitJourneySubject $data */
            $data = $event->getData();

            $choiceOptions = [];
            foreach($this->midpointDataMapper->getDisallowedChoices($data) as $disallowedChoice) {
                $key = $disallowedChoice === 'home' ?
                    'journey.locations.choices.home' :
                    $disallowedChoice;

                $choiceOptions[$key] = ['disabled' => true];
            }

            $this->addLocationFields(
                'split-journey',
                'midpoint',
                $event->getForm(),
                $options,
                [
                    'label_is_page_heading' => true,
                    'label_attr' => ['class' => 'govuk-label--l'],
                    'constraints' => new NotNull([
                        'groups' => 'wizard.split-journey.midpoint',
                        'message' => "wizard.split-journey.midpoint-choice.not-null"
                    ]),
                    'choice_options' => $choiceOptions,
                ]
            );
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'data_class' => SplitJourneySubject::class,
            'error_mapping' => [
                '.' => 'midpointLocation'
            ],
            'translation_domain' => 'travel-diary',
            'validation_groups' => function(FormInterface $form) {
                // Methodology copied from LocationsType

                // Examines the data and chooses the validation_groups based upon it, to enable validation of the
                // other field, for which errors then gets mapped to the correct form field.
                $groups = ['wizard.split-journey.midpoint'];

                [$formChoice/* , $formLocation*/] = $this->midpointDataMapper->getNormalisedFormData($form);

                if ($formChoice === self::CHOICE_OTHER) {
                    $groups[] = 'wizard.split-journey.midpoint-other';
                }

                return $groups;
            }
        ]);
    }
}