<?php

namespace App\Form\TravelDiary\StageWizard;

use App\Entity\Embeddable\Distance;
use App\Entity\Journey\Stage;
use Ghost\GovUkFrontendBundle\Form\Type\FieldsetType;
use Ghost\GovUkFrontendBundle\Form\Type\NumberType;
use Ghost\GovUkFrontendBundle\Form\Type\ValueUnitType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DetailsType extends AbstractType
{
    const INJECT_TRANSLATION_PARAMETERS = [
        'method_type' => 'method.type',
    ];

    const VALUE_OPTIONS = [
        'label' => 'unit.distance.value.label',
        'translation_domain' => 'messages',
        'number_type' => NumberType::TYPE_DECIMAL,
        'attr' => ['class' => 'govuk-input--width-5'],
    ];
    const UNIT_OPTIONS = [
        'label' => 'unit.distance.unit.label',
        'translation_domain' => 'messages',
        'choices' => Distance::UNIT_CHOICES,
        'choice_translation_domain' => 'messages',
    ];

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $transPrefix = "stage.details";
        $builder
            ->add('distanceTravelled', ValueUnitType::class, [
                'label' => "$transPrefix.distance-travelled.label",
                'help' => "$transPrefix.distance-travelled.help",
                'help_html' => 'markdown',
                'label_attr' => ['class' => 'govuk-label--m'],
                'value_options' => self::VALUE_OPTIONS,
                'unit_options' => self::UNIT_OPTIONS,
                'data_class' => Distance::class,
                'allow_empty' => true,
            ])
            ->add('travelTime', NumberType::class, [
                'label' => "$transPrefix.travel-time.label",
                'help' => "$transPrefix.travel-time.help",
                'label_attr' => ['class' => 'govuk-label--m'],
                'attr' => ['class' => 'govuk-input--width-5'],
                'help_html' => 'markdown',
                'suffix' => "$transPrefix.travel-time.units",
            ])
            ->add('companions', FieldsetType::class, [
                'label' => "$transPrefix.companions.label",
                'help' => "$transPrefix.companions.help",
                'label_attr' => ['class' => 'govuk-label--m'],
                'help_html' => 'markdown',
                'attr' => ['class' => 'govuk-form-group--inline'],
            ]);
        $builder->get('companions')
            ->add('adultCount', NumberType::class, [
                'label' => "$transPrefix.adult-count.label",
                'label_attr' => ['class' => 'govuk-label--s'],
                'help' => "$transPrefix.adult-count.help",
                'attr' => ['class' => 'govuk-input--width-5'],
            ])
            ->add('childCount', NumberType::class, [
                'label' => "$transPrefix.child-count.label",
                'label_attr' => ['class' => 'govuk-label--s'],
                'help' => "$transPrefix.child-count.help",
                'attr' => ['class' => 'govuk-input--width-5'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Stage::class,
            'validation_groups' => ['wizard.stage.details', 'embedded.distance'],
            'translation_domain' => 'travel-diary',
        ]);
    }
}
