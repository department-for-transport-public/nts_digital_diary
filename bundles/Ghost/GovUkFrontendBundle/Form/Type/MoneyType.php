<?php

namespace Ghost\GovUkFrontendBundle\Form\Type;

use Ghost\GovUkFrontendBundle\Form\DataTransformer\BigDecimalToStringTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MoneyType extends AbstractType
{
    public function getParent(): string
    {
        return InputType::class;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addViewTransformer(
            new BigDecimalToStringTransformer(
                $options['scale'],
                $options['invalid_message'],
                $options['invalid_message_parameters'],
                false,
            )
        );
    }


    public function getBlockPrefix(): string
    {
        return 'gds_money';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'compound' => false,
            'prefix' => '£',
            'scale' => 2,
            'invalid_message' => 'common.number.invalid',
            'invalid_message_parameters' => [],
        ]);
    }
}
