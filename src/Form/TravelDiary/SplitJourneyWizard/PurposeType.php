<?php

namespace App\Form\TravelDiary\SplitJourneyWizard;

use App\FormWizard\TravelDiary\SplitJourneySubject;
use Ghost\GovUkFrontendBundle\Form\Type\InputType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Event\PostSetDataEvent;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PurposeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->addEventListener(FormEvents::POST_SET_DATA, function(PostSetDataEvent $event) {
                $form = $event->getForm();

                /** @var SplitJourneySubject $data */
                $data = $event->getData();
                $destinationJourney = $data->getDestinationJourney();

                $form
                    ->add('purpose', InputType::class, [
                        'property_path' => 'destinationJourney.purpose',
                        'label_is_page_heading' => true,
                        'label' => 'split-journey.purpose.label',
                        'label_attr' => ['class' => 'govuk-label--l'],
                        'help' => 'split-journey.purpose.help',
                        'help_html' => 'markdown',
                        'label_translation_parameters' => [
                            'start' => $destinationJourney->getStartLocationForDisplay(),
                            'end' => $destinationJourney->getEndLocationForDisplay(),
                        ],
                    ]);

            });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'data_class' => SplitJourneySubject::class,
            'translation_domain' => 'travel-diary',
            'validation_groups' => 'wizard.split-journey.purpose',
        ]);
    }
}