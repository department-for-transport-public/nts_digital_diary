<?php

namespace App\Form\TravelDiary\StageWizard;

use App\Entity\Journey\Stage;
use Ghost\GovUkFrontendBundle\Form\Type\MoneyType;
use Ghost\GovUkFrontendBundle\Form\Type\NumberType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TicketCostAndBoardingsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $transPrefix = "stage.ticket-cost-and-boardings";

        $builder
            ->add('ticketCost', MoneyType::class, [
                'label' => "$transPrefix.ticket-cost.label",
                'label_attr' => ['class' => 'govuk-label--m'],
                'help_html' => 'markdown',
                'help' => "$transPrefix.ticket-cost.help",
                'attr' => ['class' => 'govuk-input--width-5'],
            ])
            ->add('boardingCount', NumberType::class, [
                'label' => "$transPrefix.boarding-count.label",
                'label_attr' => ['class' => 'govuk-label--m'],
                'help' => "$transPrefix.boarding-count.help",
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