<?php

namespace App\Form\TravelDiary;

use App\Entity\DiaryDay;
use Ghost\GovUkFrontendBundle\Form\Type\ButtonGroupType;
use Ghost\GovUkFrontendBundle\Form\Type\ButtonType;
use Ghost\GovUkFrontendBundle\Form\Type\LinkType;
use Ghost\GovUkFrontendBundle\Form\Type\TextareaType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NotesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $translationPrefix = $options['translation_prefix'];
        $notesField = $options['notes_field'];

        $builder
            ->add($notesField, TextareaType::class, [
                'label' => "{$translationPrefix}.page-title",
                'label_is_page_heading' => true,
                'label_attr' => ['class' => 'govuk-heading-xl'],
                'label_translation_parameters' => $options['notes_translation_parameters'],
            ])
            ->add('button_group', ButtonGroupType::class);

        $builder
            ->get('button_group')
            ->add('save', ButtonType::class, [
                'label' => "{$translationPrefix}.save",
            ])
            ->add('cancel', LinkType::class, [
                'label' => 'actions.cancel',
                'translation_domain' => 'messages',
                'href' => $options['cancel_link_href'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => DiaryDay::class,
            'translation_domain' => 'travel-diary',
            'notes_translation_parameters' => [],
        ]);

        $resolver->setRequired([
            'cancel_link_href',
            'notes_field',
            'translation_prefix',
        ]);
    }
}