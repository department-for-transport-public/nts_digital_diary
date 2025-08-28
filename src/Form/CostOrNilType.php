<?php

namespace App\Form;

use App\Entity\Embeddable\CostOrNil;
use App\Form\OldGhostBundle\MoneyType;
use Ghost\GovUkFrontendBundle\Form\Type\BooleanChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CostOrNilType extends AbstractType
{
    public const string BOOLEAN_FIELD_NAME = 'hasCost';
    public const string COST_FIELD_NAME = 'cost';
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $translationPrefix = $options['translation_prefix'];
        $builder
            ->setDataMapper(new CostOrNilDataMapper())
            ->add(self::BOOLEAN_FIELD_NAME, BooleanChoiceType::class, [
                'label' => "{$translationPrefix}.boolean.label",
                'label_attr' => ['class' => $options['label_class']],
                'help' => "{$translationPrefix}.boolean.help",
                'help_html' => 'markdown',
                'choice_options' => [
                    'boolean.true' => ['conditional_form_name' => self::COST_FIELD_NAME],
                ],
                'label_translation_parameters' => $options['translation_parameters'],
                'help_translation_parameters' => $options['translation_parameters'],
                'choice_translation_parameters'  => $options['translation_parameters'],
            ])
            ->add(self::COST_FIELD_NAME, MoneyType::class, [
                'attr' => ['class' => 'govuk-input--width-5'],
                'label' => "{$translationPrefix}.cost.label",
                'label_attr' => ['class' => 'govuk-!-font-weight-bold'],
                'help' => "{$translationPrefix}.cost.help",
                'label_translation_parameters' => $options['translation_parameters'],
                'help_translation_parameters' => $options['translation_parameters'],
            ])
            ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'translation_domain' => 'travel-diary',
            'label' => false,
            'data_class' => CostOrNil::class,
            'translation_parameters' => [],
            'error_bubbling' => false,
            'label_class' => 'govuk-fieldset__legend--m',
        ]);
        $resolver->setRequired([
            'translation_prefix',
        ]);
    }
}