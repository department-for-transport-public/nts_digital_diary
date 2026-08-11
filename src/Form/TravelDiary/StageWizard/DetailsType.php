<?php

namespace App\Form\TravelDiary\StageWizard;

use App\Entity\Embeddable\Distance;
use App\Entity\Journey\Stage;
use App\Form\OldGhostBundle\NumberType;
use Ghost\GovUkFrontendBundle\Form\Type\FieldsetType;
use Ghost\GovUkFrontendBundle\Form\Type\ValueUnitType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DetailsType extends AbstractType
{
    public const array INJECT_TRANSLATION_PARAMETERS = [
        'method_type' => 'method.type',
    ];

    public const array VALUE_OPTIONS = [
        'label' => 'unit.distance.value.label',
        'translation_domain' => 'messages',
        'number_type' => NumberType::TYPE_DECIMAL,
        'attr' => ['class' => 'govuk-input--width-5', 'inputmode' => 'decimal'],
    ];
    public const array UNIT_OPTIONS = [
        'label' => 'unit.distance.unit.label',
        'translation_domain' => 'messages',
        'choices' => Distance::UNIT_CHOICES,
        'choice_translation_domain' => 'messages',
    ];

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('distanceTravelled', ValueUnitType::class, [
                'label' => "stage.details.distance-travelled.label",
                'label_translation_parameters' => ['wizard' => $options['wizard_source']],
                'help' => "stage.details.distance-travelled.help",
                'help_html' => 'markdown',
                'label_attr' => ['class' => 'govuk-fieldset__legend--m'],
                'value_form_type' => NumberType::class,
                'value_options' => self::VALUE_OPTIONS,
                'unit_options' => self::UNIT_OPTIONS,
                'data_class' => Distance::class,
                'allow_empty' => true,
            ])
            ->add('travelTime', NumberType::class, [
                'label' => "stage.details.travel-time.label",
                'help' => "stage.details.travel-time.help",
                'label_attr' => ['class' => 'govuk-label--m'],
                'attr' => ['class' => 'govuk-input--width-5'],
                'help_html' => 'markdown',
                'suffix' => "stage.details.travel-time.units",
            ])
            ->add('companions', FieldsetType::class, [
                'label' => "stage.details.companions.label",
                'help' => "stage.details.companions.help",
                'label_attr' => ['class' => 'govuk-fieldset__legend--m'],
                'help_html' => 'markdown',
                'attr' => ['class' => 'govuk-form-group--inline'],
            ]);
        $builder->get('companions')
            ->add('adultCount', NumberType::class, [
                'label' => "stage.details.adult-count.label",
                'label_attr' => ['class' => 'govuk-label--s'],
                'help' => "stage.details.adult-count.help",
                'attr' => ['class' => 'govuk-input--width-5'],
            ])
            ->add('childCount', NumberType::class, [
                'label' => "stage.details.child-count.label",
                'label_attr' => ['class' => 'govuk-label--s'],
                'help' => "stage.details.child-count.help",
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
            'wizard_source' => 'legacy',
        ]);
    }
}
