<?php

namespace Ghost\GovUkFrontendBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\RadioType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConditionalTypeExtension extends AbstractTypeExtension
{
    public static function getExtendedTypes(): iterable
    {
        return [
            RadioType::class,
            CheckboxType::class,
        ];
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'conditional_form_name' => null,
            'conditional_hide_form_names' => null,
        ]);

        $resolver->setAllowedTypes('conditional_form_name', ['null', 'string']);
        $resolver->setAllowedTypes('conditional_hide_form_names', ['null', 'string[]']);
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);

        $view->vars['conditional_form_name'] = $options['conditional_form_name'] ?? false;
        $view->vars['conditional_hide_form_names'] = $options['conditional_hide_form_names'] ?? false;
    }
}