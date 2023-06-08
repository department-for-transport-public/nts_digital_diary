<?php

namespace App\Form\TravelDiary\ShareJourneyWizard;

use App\Entity\Journey\Method;
use App\Entity\Journey\Stage;
use App\Form\CostOrNilType;
use Ghost\GovUkFrontendBundle\Form\Type\BooleanChoiceType;
use Ghost\GovUkFrontendBundle\Form\Type\InputType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Event\PreSetDataEvent;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StageDetailsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function(PreSetDataEvent $event) use ($options) {
            /** @var Stage $stage */
            $stage = $event->getData();
            $sourceStage = $stage->getJourney()->getSharedFrom()->getStageByNumber($stage->getNumber());
            $form = $event->getForm();
            $translationParams = [
                'name' => $stage->getJourney()->getDiaryDay()->getDiaryKeeper()->getName(),
                'stageNumber' => $stage->getNumber(),
            ];

            switch ($stage->getMethod()->getType()) {
                case Method::TYPE_PRIVATE:
                    $form
                        ->add("isDriver", BooleanChoiceType::class, [
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
                            'invalid_message_parameters' => $translationParams,
                        ])
                        ->add("parkingCost", CostOrNilType::class, [
                            'translation_prefix' => 'share-journey.stage-details.parking-cost',
                            'translation_parameters' => $translationParams,
                            'row_attr' => ['class' => 'govuk-grid-column-one-half'],
                            'label_class' => 'govuk-label--s',
                        ]);
                    break;

                case Method::TYPE_PUBLIC:
                    $form
                        ->add("ticketType", InputType::class, [
                            'label' => "share-journey.stage-details.ticket-type.label",
                            'label_attr' => ['class' => 'govuk-label--s'],
                            'label_translation_parameters' => $translationParams,
                            'row_attr' => ['class' => 'govuk-grid-column-one-half'],
                        ])
                        ->add("ticketCost", CostOrNilType::class, [
                            'translation_prefix' => "share-journey.stage-details.ticket-cost",
                            'translation_parameters' => $translationParams,
                            'row_attr' => ['class' => 'govuk-grid-column-one-half'],
                            'label_class' => 'govuk-label--s',
                        ]);
                    break;
            }
        });
    }

    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['label_translation_parameters'] = array_merge(
            $view->children['isDriver']?->vars['label_translation_parameters'] ?? [],
            $view->children['ticketType']?->vars['label_translation_parameters'] ?? [],
            $view->vars['translation_parameters'] ?? []
        );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'label' => 'share-journey.stage-details.shared-with-section-title',
            'label_attr' => ['class' =>  'govuk-label--m'],
            'data_class' => Stage::class,
            'translation_domain' => 'travel-diary',
            'row_attr' => [
                'class' => 'govuk-!-margin-bottom-6',
            ],
        ]);
    }
}