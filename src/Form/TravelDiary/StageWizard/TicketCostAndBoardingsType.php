<?php

namespace App\Form\TravelDiary\StageWizard;

use App\Entity\Journey\Stage;
use App\Form\CostOrNilType;
use App\Form\OldGhostBundle\NumberType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TicketCostAndBoardingsType extends AbstractType
{
    public const array INJECT_TRANSLATION_PARAMETERS = [
        'stage_method_key' => 'methodKeyForTranslationParameterInjection',
        'stage_method' => 'methodForDisplayWithoutTypePrefixWhenOther',
    ];

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('ticketCost', CostOrNilType::class, [
                'translation_prefix' => "stage.ticket-cost-and-boardings.ticket-cost",
            ])
            ->add('boardingCount', NumberType::class, [
                'label' => "stage.ticket-cost-and-boardings.boarding-count.label",
                'label_attr' => ['class' => 'govuk-label--m'],
                'help' => "stage.ticket-cost-and-boardings.boarding-count.help",
                'help_translation_parameters' => ['wizard' => $options['wizard_source']],
                'attr' => ['class' => 'govuk-input--width-5'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Stage::class,
            'translation_domain' => 'travel-diary',
            'validation_groups' => 'wizard.ticket-cost-and-boardings',
            'wizard_source' => 'legacy',
        ]);
    }
}