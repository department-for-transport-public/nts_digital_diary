<?php

namespace App\Form\TravelDiary\ShareJourneyWizard;

use App\Entity\Journey\Journey;
use Ghost\GovUkFrontendBundle\Form\Type\InputType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Event\PreSetDataEvent;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PurposeType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->addEventListener(FormEvents::PRE_SET_DATA, function(PreSetDataEvent $event) {
                /** @var Journey $journey */
                $journey = $event->getData();
                $diaryKeeper = $journey->getDiaryDay()->getDiaryKeeper();
                $form = $event->getForm();
                $form
                    ->add('purpose', InputType::class, [
                        'label' => 'share-journey.purposes.purpose.label',
                        'label_attr' => ['class' => 'govuk-label--s'],
                        'label_translation_parameters' => ['name' => $diaryKeeper->getName()],
                    ]);
            })
            ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Journey::class,
            'label' => false,
        ]);
    }
}