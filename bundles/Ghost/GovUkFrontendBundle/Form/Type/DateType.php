<?php

namespace Ghost\GovUkFrontendBundle\Form\Type;

use ReflectionException;
use ReflectionProperty;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType as ExtendedDateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormConfigBuilder;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DateType extends AbstractType
{
    public function getParent(): string
    {
        return ExtendedDateType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'gds_date';
    }

    /**
     * @throws ReflectionException
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        foreach (['year', 'month', 'day'] as $item)
        if ($builder->has($item)) {
            $formConfig = $builder->get($item);
            if (!$formConfig->getOption('translation_domain')) {
                $reflectionOptions = new ReflectionProperty(FormConfigBuilder::class, 'options');
                $reflectionOptions->setAccessible(true);
                $reflectionOptions->setValue($formConfig, array_merge($formConfig->getOptions(), $options["{$item}_options"]));
            }
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'widget' => 'text',
            'invalid_message' => 'common.date.invalid',
            'year_options' => [
                'label' => 'date.year',
                'translation_domain' => 'messages',
            ],
            'month_options' => [
                'label' => 'date.month',
                'translation_domain' => 'messages',
            ],
            'day_options' => [
                'label' => 'date.day',
                'translation_domain' => 'messages',
            ],
        ]);
        $resolver->setAllowedValues('widget', ['text']);

        $resolver->setDefault('format', 'dd-MM-yyyy');
    }
}
