<?php

namespace App\Form\TravelDiary\ShareJourneyWizard;

use App\Entity\Journey\Method;
use App\Entity\Journey\Stage;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Form;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StageDetailsCollectionType extends AbstractType
{
    public function getParent(): string
    {
        return CollectionType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'allow_add' => false,
            'allow_delete' => false,
            'validation_groups' => function(Form $form): array {
                /** @var Stage $firstStage */
                $firstStage = $form->getData()->first();
                return match($firstStage->getMethod()->getType()) {
                    Method::TYPE_PUBLIC => ['wizard.share-journey.ticket-type-and-cost'],
                    Method::TYPE_PRIVATE => ['wizard.share-journey.driver-and-parking'],
                    default => []
                };
            },
            'entry_type' => StageDetailsType::class,
            'translation_domain' => 'travel-diary',
        ]);
    }
}