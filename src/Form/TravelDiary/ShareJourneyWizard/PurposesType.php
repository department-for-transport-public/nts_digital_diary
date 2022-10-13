<?php

namespace App\Form\TravelDiary\ShareJourneyWizard;

use App\Entity\DiaryKeeper;
use App\Entity\Journey\Journey;
use Ghost\GovUkFrontendBundle\Form\Type\InputType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Event\PreSetDataEvent;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PurposesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->setDataMapper(new PurposesDataMapper())
            ->addEventListener(FormEvents::PRE_SET_DATA, function(PreSetDataEvent $event) {
                /** @var Journey $sourceJourney */
                $sourceJourney = $event->getData();
                $form = $event->getForm();

                /**
                 * @var Journey $sharedJourney
                 * @var DiaryKeeper $diaryKeeper
                 */
                foreach($sourceJourney->getSharedToJourneysBeingAdded() as $sharedJourney) {
                    $diaryKeeper = $sharedJourney->getDiaryDay()->getDiaryKeeper();
                    $diaryKeeperId = $diaryKeeper->getId();
                    $form->add("purpose-{$diaryKeeperId}", InputType::class, [
                        'label' => 'share-journey.purposes.purpose.label',
                        'label_attr' => ['class' => 'govuk-label--s'],
                        'label_translation_parameters' => ['name' => $diaryKeeper->getName()],
                        'translation_domain' => 'travel-diary',
                    ]);
                }
            });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Journey::class,
            'validation_groups' => 'wizard.share-journey.purposes',
        ]);
    }
}