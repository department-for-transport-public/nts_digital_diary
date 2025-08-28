<?php

namespace App\Form\OldGhostBundle;

use App\Form\OldGhostBundle\DataTransformer\TimeStringTransformer;
use App\Utility\DateTimeFormats;
use Ghost\GovUkFrontendBundle\Form\Type\ChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

class TimeType extends AbstractType
{
    public const string MODE_12_HOUR = DateTimeFormats::MODE_12_HOUR;
    public const string MODE_24_HOUR = DateTimeFormats::MODE_24_HOUR;

    public const string AM = 'am';
    public const string PM = 'pm';

    public function getBlockPrefix(): string
    {
        return 'gds_time';
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $subFieldOptions = [
            'error_bubbling' => true,
            'translation_domain' => 'messages',
        ];

        $amOrPmOptions = [];

        if ($options['meridiem_choice_expanded'] === false) {
            $amOrPmOptions['placeholder'] = '';
            $amOrPmOptions['attr'] = ['class' => 'govuk-time-input__select govuk-input--width-2'];
        }

        $builder
            ->add('hour', TimeInputType::class, array_merge($subFieldOptions, [
                'label' => 'time.hour.label',
            ]))
            ->add('minute', TimeInputType::class, array_merge($subFieldOptions, [
                'label' => 'time.minute.label',
            ]))
            ->addModelTransformer(new TimeStringTransformer($options['mode']))
        ;
        if ($options['mode'] === self::MODE_12_HOUR) {
            $builder
                ->add('am_or_pm', ChoiceType::class, array_merge($subFieldOptions, [
                    'row_attr' => ['class' => 'govuk-time-input__item'],
                    'label' => 'time.meridiem.label',
                    'label_attr' => ['class' => 'govuk-time-input__label'],
                    'attr' => ['class' => 'govuk-time-input__radios'],
                    'choices' => [
                        'time.meridiem.am.label' => self::AM,
                        'time.meridiem.pm.label' => self::PM,
                    ],
                    'expanded' => $options['meridiem_choice_expanded'],
//                    'fieldset' => false,
                ], $amOrPmOptions));
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(['mode']);
        $resolver->addAllowedValues('mode', [self::MODE_12_HOUR, self::MODE_24_HOUR]);
        $resolver->setDefaults([
            'help' => new TranslatableMessage('time.{mode}.help', [], 'messages'),
            'error_bubbling' => false,
            'meridiem_choice_expanded' => true,
            'attr' => ['class' => 'govuk-time-input govuk-time-input--{mode}'],
        ]);
        foreach(['help', 'attr'] as $opt) {
            $resolver->setNormalizer($opt, $this->normalizeOptionWithMode(...));
        }
    }

    protected function normalizeOptionWithMode(Options $options, string|array|TranslatableMessage $value): mixed
    {
        return $this->strReplaceOption(
            ['{mode}'],
            [$options['mode']],
            $value
        );
    }

    protected function strReplaceOption(string|array $search, string|array $replace, string|array|TranslatableMessage $value): mixed
    {
        return match(true) {
            is_string($value) => str_replace($search, $replace, $value),
            is_array($value) => array_map(fn($v): mixed => $this->strReplaceOption($search, $replace, $v), $value),
            $value instanceof TranslatableMessage => new TranslatableMessage(
                $this->strReplaceOption($search, $replace, $value->getMessage()),
                $value->getParameters(),
                $value->getDomain()
            )
        };
    }
}
