<?php

namespace Ghost\GovUkFrontendBundle\Form\Type;

use Ghost\GovUkFrontendBundle\Form\DataTransformer\CostTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MoneyType extends AbstractType
{
    public function getParent(): string
    {
        return InputType::class;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addViewTransformer(new CostTransformer($options['divisor']));
    }


    public function getBlockPrefix(): string
    {
        return 'gds_money';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'compound' => false,
            'divisor' => 100,
            'prefix' => '£',
        ]);
    }
}
