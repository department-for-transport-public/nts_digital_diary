<?php

namespace Ghost\GovUkFrontendBundle\Form\Type;

use Ghost\GovUkFrontendBundle\Form\DataTransformer\BigDecimalToStringTransformer;
use Ghost\GovUkFrontendBundle\Form\DataTransformer\IntegerToStringTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NumberType extends AbstractType
{
    const TYPE_INTEGER = 'integer';
    const TYPE_DECIMAL = 'decimal';

    public function getParent(): string
    {
        return InputType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'gds_number';
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        // N.B. Do *not* set inputmode or pattern when asking for decimal numbers, as per:
        //      https://design-system.service.gov.uk/components/text-input/#asking-for-decimal-numbers

        if ($options['number_type'] === self::TYPE_INTEGER) {
            $view->vars['attr'] = array_merge([
                'inputmode' => 'numeric',
                'pattern' => '[0-9]*',
            ], $view->vars['attr']);
        }

        // N.B. Number inputs must use type="text" and *not* type="number" as per:
        //      https://design-system.service.gov.uk/components/text-input/#avoid-using-inputs-with-a-type-of-number
        //      https://technology.blog.gov.uk/2020/02/24/why-the-gov-uk-design-system-team-changed-the-input-type-for-numbers/
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $transformer = match($options['number_type'])
        {
            self::TYPE_DECIMAL => new BigDecimalToStringTransformer($options['scale'], $options['transformer_invalid_message']),
            self::TYPE_INTEGER => new IntegerToStringTransformer($options['transformer_invalid_message']),
        };

        $builder->addModelTransformer($transformer);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'number_type' => self::TYPE_INTEGER,
            'scale' => 2,
            'transformer_invalid_message' => 'common.number.invalid',
            'type' => null, // Should always be left as null ("text"), but included since some of the tests override it
        ]);

        $resolver->setAllowedValues('number_type', [
            self::TYPE_DECIMAL,
            self::TYPE_INTEGER,
        ]);
    }
}
