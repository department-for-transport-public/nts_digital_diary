<?php

namespace Ghost\GovUkFrontendBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Positive;

class MoneyNilType extends AbstractType
{
    public const BOOLEAN_FIELD_NAME = 'has_paid';
    public const COST_FIELD_NAME = 'cost';
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $translationPrefix = $options['translation_prefix'];
        $builder
            ->addModelTransformer(new CallbackTransformer(
                fn($data) => [
                    self::BOOLEAN_FIELD_NAME => is_null($data) ? null : $data !== 0,
                    self::COST_FIELD_NAME => $data !== 0 ? $data : null,
                ],
                fn ($data) => is_null($data[self::BOOLEAN_FIELD_NAME])
                    ? null
                    : ($data[self::BOOLEAN_FIELD_NAME] ? $data[self::COST_FIELD_NAME] : 0)
            ))
            ->add(self::BOOLEAN_FIELD_NAME, BooleanChoiceType::class, [
                'label' => "{$translationPrefix}.boolean.label",
                'label_attr' => ['class' => 'govuk-label--m'],
                'help' => "{$translationPrefix}.boolean.help",
                'help_html' => 'markdown',
                'choice_options' => [
                    'boolean.true' => ['conditional_form_name' => self::COST_FIELD_NAME],
                ],
                'attr' => ['class' => ''], // needed to revert the default inline style
            ])
            ->add(self::COST_FIELD_NAME, MoneyType::class, [
                'attr' => ['class' => 'govuk-input--width-5'],
                'label' => "{$translationPrefix}.cost.label",
                'label_attr' => ['class' => 'govuk-label--s'],
                'help' => "{$translationPrefix}.cost.help",
                'constraints' => [
                    new NotBlank(message: "wizard.{$translationPrefix}.not-blank", allowNull: false, groups: ['cost.required']),
                    new Positive(message: "wizard.{$translationPrefix}.positive", groups: ['cost.positive']),
                ],
                'validation_groups' => fn(Form $f) =>
                    ($f->getParent()->getViewData()[self::BOOLEAN_FIELD_NAME] === true)
                        ? ['cost.required', 'cost.positive']
                        : ["wizard.{$translationPrefix}.boolean"],
            ])
            ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'translation_domain' => 'travel-diary',
            'label' => false,
        ]);
        $resolver->setRequired([
            'translation_prefix',
        ]);
    }
}