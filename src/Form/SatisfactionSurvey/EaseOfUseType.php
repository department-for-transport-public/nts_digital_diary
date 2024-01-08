<?php

namespace App\Form\SatisfactionSurvey;

use App\Entity\SatisfactionSurvey;
use Ghost\GovUkFrontendBundle\Form\Type\ChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EaseOfUseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $easeOfUseChoices = [
            '1-very-easy',
            '2-somewhat-easy',
            '3-neither-easy-nor-difficult',
            '4-somewhat-difficult',
            '5-very-difficult',
        ];

        $builder->add('easeRating', ChoiceType::class, [
            'label' => 'satisfaction.ease-of-use.label',
            'help' => 'satisfaction.ease-of-use.help',
            'choices' => array_combine(
                array_map(fn(string $choice) => "satisfaction.ease-of-use.choices.{$choice}", $easeOfUseChoices),
                $easeOfUseChoices,
            ),
            'label_attr' => ['class' => 'govuk-label--m']
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SatisfactionSurvey::class,
            'translation_domain' => 'travel-diary',
            'validation_groups' => ['wizard.satisfaction-survey.ease-of-use'],
        ]);
    }
}