<?php

namespace App\Form\TravelDiary\ShareJourneyWizard;

use App\Entity\DiaryKeeper;
use App\Entity\Journey\Journey;
use Ghost\GovUkFrontendBundle\Form\Type\InputType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Event\PreSetDataEvent;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PurposeCollectionType extends AbstractType
{
    public function getParent(): string
    {
        return CollectionType::class;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'validation_groups' => 'wizard.share-journey.purpose',
            'entry_type' => PurposeType::class,
            'translation_domain' => 'travel-diary',
        ]);
    }
}