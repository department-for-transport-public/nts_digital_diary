<?php

namespace App\Form\Admin\Feedback;

use App\Entity\Feedback\Note;
use Ghost\GovUkFrontendBundle\Form\Type\ButtonType;
use Ghost\GovUkFrontendBundle\Form\Type\TextareaType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NoteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('note', TextareaType::class, [
                'label' => false,
            ])
            ->add('submit', ButtonType::class, [
                'label' => 'feedback.view.add-note.submit.label'
            ])
            ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Note::class,
            'validation_groups' => ['feedback.note'],
            'translation_domain' => 'admin',
        ]);
    }
}