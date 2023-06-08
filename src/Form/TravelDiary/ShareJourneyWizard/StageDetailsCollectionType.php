<?php

namespace App\Form\TravelDiary\ShareJourneyWizard;

use App\Entity\DiaryKeeper;
use App\Entity\Journey\Journey;
use App\Entity\Journey\Method;
use App\Entity\Journey\Stage;
use Ghost\GovUkFrontendBundle\Form\Type\InputType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Event\PreSetDataEvent;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\Test\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StageDetailsCollectionType extends AbstractType
{
    public function getParent(): string
    {
        return CollectionType::class;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'allow_add' => false,
            'allow_delete' => false,
            'validation_groups' => function(Form $form) {
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