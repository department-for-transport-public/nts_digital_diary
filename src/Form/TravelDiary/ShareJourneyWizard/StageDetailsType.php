<?php

namespace App\Form\TravelDiary\ShareJourneyWizard;

use App\Entity\Journey\Method;
use App\Entity\Journey\Stage;
use Ghost\GovUkFrontendBundle\Form\Type\BooleanChoiceType;
use Ghost\GovUkFrontendBundle\Form\Type\FieldsetType;
use Ghost\GovUkFrontendBundle\Form\Type\InputType;
use Ghost\GovUkFrontendBundle\Form\Type\MoneyType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Event\PreSetDataEvent;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StageDetailsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->setDataMapper(new StageDetailsDataMapper());

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function(PreSetDataEvent $event) use ($options) {
            /** @var Stage $sourceStage */
            $sourceStage = $event->getData();
            $form = $event->getForm();

            foreach ($sourceStage->getJourney()->getSharedToJourneysBeingAdded() as $diaryKeeperId => $targetJourney) {
                $diaryKeeper = $targetJourney->getDiaryDay()->getDiaryKeeper();
                $translationParams = ['name' => $diaryKeeper->getName()];

                $form
                    ->add("participant-{$diaryKeeperId}", FieldsetType::class, [
                        'label' => "share-journey.stage-details.participant.label",
                        'label_translation_parameters' => $translationParams,
                        'label_attr' => ['class' => 'govuk-label--m govuk-grid-column-full'],
                        'attr' => ['class' => 'govuk-grid-row fieldset--share-stage-details'],
                    ]);

                $participantForm = $form->get("participant-{$diaryKeeperId}");

                switch ($sourceStage->getMethod()->getType()) {
                    case Method::TYPE_PRIVATE:
                        $participantForm
                            ->add("isDriver-{$diaryKeeperId}", BooleanChoiceType::class, [
                                'label' => "share-journey.stage-details.driver-or-passenger.label",
                                'label_attr' => ['class' => 'govuk-label--s'],
                                'label_translation_parameters' => $translationParams,
                                'help' => "share-journey.stage-details.driver-or-passenger.help",
                                'row_attr' => ['class' => 'govuk-grid-column-one-half'],
                                'choices' => [
                                    "share-journey.stage-details.driver-or-passenger.choices.driver" => "true",
                                    "share-journey.stage-details.driver-or-passenger.choices.passenger" => "false",
                                ],
                                'choice_translation_domain' => $options['translation_domain'],
                                // If the source stage was marked as the driver, then the cloned stages get
                                // automatically marked as passengers
                                'disabled' => $sourceStage->getIsDriver() === true,
                            ])
                            ->add("parkingCost-{$diaryKeeperId}", MoneyType::class, [
                                'label' => 'share-journey.stage-details.parking-cost.label',
                                'label_attr' => ['class' => 'govuk-label--s'],
                                'label_translation_parameters' => $translationParams,
                                'row_attr' => ['class' => 'govuk-grid-column-one-half'],
                                'attr' => ['class' => 'govuk-input--width-5'],
                            ]);
                        break;

                    case Method::TYPE_PUBLIC:
                        $participantForm
                            ->add("ticketType-{$diaryKeeperId}", InputType::class, [
                                'label' => "share-journey.stage-details.ticket-type.label",
                                'label_attr' => ['class' => 'govuk-label--s'],
                                'label_translation_parameters' => $translationParams,
                                'row_attr' => ['class' => 'govuk-grid-column-one-half'],
                            ])
                            ->add("ticketCost-{$diaryKeeperId}", MoneyType::class, [
                                'label' => "share-journey.stage-details.ticket-cost.label",
                                'label_attr' => ['class' => 'govuk-label--s'],
                                'label_translation_parameters' => $translationParams,
                                'attr' => ['class' => 'govuk-input--width-5'],
                                'row_attr' => ['class' => 'govuk-grid-column-one-half'],
                            ]);
                        break;
                }
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Stage::class,
            'translation_domain' => 'travel-diary',
            'validation_groups' => function(FormInterface $form) {
                $data = $form->getData();
                $type = $data->getMethod()->getType();

                $mappings = [
                    Method::TYPE_PRIVATE => ['wizard.share-journey.driver-and-parking'],
                    Method::TYPE_PUBLIC => ['wizard.share-journey.ticket-type-and-cost'],
                ];

                return $mappings[$type] ?? null;
            },
        ]);
    }
}