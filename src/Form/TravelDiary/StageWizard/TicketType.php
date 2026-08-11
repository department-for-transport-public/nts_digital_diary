<?php

namespace App\Form\TravelDiary\StageWizard;

use App\Entity\Journey\Stage;
use Ghost\GovUkFrontendBundle\Form\Type\InputType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TicketType extends AbstractType
{
    public const INJECT_TRANSLATION_PARAMETERS = [
        'stage_number' => 'number',
    ];

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isSubForm = $options['sub_form_label'] !== false;

        $builder
            ->add('ticketType', InputType::class, [
                'label_is_page_heading' => !$isSubForm,
                'label' => $isSubForm ? $options['sub_form_label'] : "stage.ticket.page-title",
                'label_attr' => ['class' => $isSubForm ? 'govuk-label--m' : 'govuk-label--l'],
                'help' => $isSubForm ? $options['sub_form_help'] : "stage.ticket.ticket-type.help",
                'help_html' => 'markdown',
            ])
            ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Stage::class,
            'validation_groups' => 'wizard.ticket-type',
            'translation_domain' => 'travel-diary',
            'sub_form_label' => false,
            'sub_form_help' => null,
        ]);
    }
}