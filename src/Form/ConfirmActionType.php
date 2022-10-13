<?php

namespace App\Form;

use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

class ConfirmActionType extends AbstractType
{
    /*
     * Investigated removing "TranslatableMessage", but using it allows the form-level translation_domain option
     * to be useful, as one can set translation_domain and confirm.label (and it works), or cancel.label can
     * additionally be set, and it still works (whereas the alternative scheme would require having separate
     * cancel.translation_domain and confirm.translation_domain options)
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('button_group', Gds\ButtonGroupType::class);

        $builder->get('button_group')
            ->add('confirm', Gds\ButtonType::class, array_merge([
                'type' => 'submit',
                'label' => new TranslatableMessage('actions.confirm', [], 'messages'),
            ], $options['confirm_button_options']))
            ->add('cancel', Gds\LinkType::class, array_merge([
                'label' => new TranslatableMessage('actions.cancel', [], 'messages'),
            ], $options['cancel_link_options']))
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'confirm_button_options' => [],
        ]);
        $resolver->setRequired('cancel_link_options');
    }
}