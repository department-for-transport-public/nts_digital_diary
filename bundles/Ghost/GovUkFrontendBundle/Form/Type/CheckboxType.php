<?php

namespace Ghost\GovUkFrontendBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType as ExtendedCheckboxType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CheckboxType extends AbstractType
{
    public function getParent(): string
    {
        return ExtendedCheckboxType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'gds_checkbox';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('small', false);
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['small'] = $options['small'];
    }
}
