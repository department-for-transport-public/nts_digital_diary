<?php

namespace App\Form\SatisfactionSurvey;

use App\Entity\SatisfactionSurvey;
use Ghost\GovUkFrontendBundle\Form\Type\BooleanChoiceType;
use Ghost\GovUkFrontendBundle\Form\Type\ChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DiaryCompletionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $howOftenEntriesAddedChoices = [
            'multiple-times-per-day',
            'once-a-day',
            'every-other-day',
            'less-often',
        ];

        $builder
            ->add('howOftenEntriesAdded', ChoiceType::class, [
                'label' => 'satisfaction.how-often-entries-added.label',
                'help' => 'satisfaction.how-often-entries-added.help',
                'choices' => array_combine(
                    array_map(fn(string $choice) => "satisfaction.how-often-entries-added.choices.{$choice}", $howOftenEntriesAddedChoices),
                    $howOftenEntriesAddedChoices,
                ),
                'label_attr' => ['class' => 'govuk-label--m'],
            ])
            ->add('writtenNoteKept', BooleanChoiceType::class, [
                'label' => 'satisfaction.written-note-kept.label',
                'help' => 'satisfaction.written-note-kept.help',
                'label_attr' => ['class' => 'govuk-label--m'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SatisfactionSurvey::class,
            'translation_domain' => 'travel-diary',
            'validation_groups' => ['wizard.satisfaction-survey.diary-completion'],
        ]);
    }
}