<?php

namespace App\Form\TravelDiary\StageWizard;

use App\Entity\Journey\Stage;
use App\Form\CostOrNilType;
use Ghost\GovUkFrontendBundle\Form\Type\NumberType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TicketCostAndBoardingsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('ticketCost', CostOrNilType::class, [
                'translation_prefix' => "stage.ticket-cost-and-boardings.ticket-cost",
            ])
            ->add('boardingCount', NumberType::class, [
                'label' => "stage.ticket-cost-and-boardings.boarding-count.label",
                'label_attr' => ['class' => 'govuk-label--m'],
                'help' => "stage.ticket-cost-and-boardings.boarding-count.help",
                'attr' => ['class' => 'govuk-input--width-5'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Stage::class,
            'translation_domain' => 'travel-diary',
            'validation_groups' => 'wizard.ticket-cost-and-boardings',
        ]);
    }
}