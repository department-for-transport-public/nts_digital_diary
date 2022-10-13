<?php

namespace Ghost\GovUkFrontendBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class EmailType extends AbstractType
{
    public function getParent(): string
    {
        return InputType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'gds_email';
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['attr'] = array_merge([
            'class' => 'govuk-input--width-20',
            'inputmode' => 'email',
        ], $view->vars['attr']);
    }
}
