<?php

namespace App\Form\TravelDiary\StageWizard;

use App\Entity\Journey\Stage;
use Ghost\GovUkFrontendBundle\Form\Type\InputType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TicketType extends AbstractType
{
    const INJECT_TRANSLATION_PARAMETERS = [
        'stage_number' => 'number',
    ];

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('ticketType', InputType::class, [
                'label_is_page_heading' => true,
                'label' => "stage.ticket.page-title",
                'label_attr' => ['class' => 'govuk-label--l'],
                'help' => "stage.ticket.ticket-type.help",
                'help_html' => 'markdown',
            ])
            ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Stage::class,
            'validation_groups' => 'wizard.ticket-type',
            'translation_domain' => 'travel-diary',
        ]);
    }
}