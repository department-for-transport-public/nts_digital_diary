<?php

namespace App\Form\TravelDiary\ShareJourneyWizard;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PurposeCollectionType extends AbstractType
{
    public function getParent(): string
    {
        return CollectionType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'validation_groups' => 'wizard.share-journey.purpose',
            'entry_type' => PurposeType::class,
            'translation_domain' => 'travel-diary',
        ]);
    }
}