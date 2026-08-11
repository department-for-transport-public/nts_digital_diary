<?php

namespace App\Form\TravelDiary\CombinedReflowWizard\Stage;

use App\Entity\Journey\Stage;
use App\Form\TravelDiary\StageWizard\TicketCostAndBoardingsType;
use App\Form\TravelDiary\StageWizard\TicketType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TicketDetailsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('ticket', TicketType::class, [
                'label' => false,
                'inherit_data' => 'true',
                'sub_form_label' => 'add-journey.stage-ticket-details.ticket-type.label',
                'sub_form_help' => 'add-journey.stage-ticket-details.ticket-type.help'
            ])
            ->add('ticketCost', TicketCostAndBoardingsType::class, [
                'label' => false,
                'inherit_data' => 'true',
                'wizard_source' => 'reflow',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Stage::class,
            'validation_groups' => ['wizard.ticket-type', 'wizard.ticket-cost-and-boardings'],
            'translation_domain' => 'travel-diary',
        ]);
    }
}