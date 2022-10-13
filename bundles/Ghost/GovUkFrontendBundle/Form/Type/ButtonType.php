<?php

namespace Ghost\GovUkFrontendBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType as ExtendedButtonType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\SubmitButtonTypeInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ButtonType extends AbstractType implements SubmitButtonTypeInterface
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'prevent_double_click' => true,
            'type' => null,
            'label_html' => false,
            'is_start_button' => false,
        ]);

        $resolver->setAllowedValues('type', [null, 'button', 'submit']);
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['type'] = $options['type'];

        if ($view->vars['type'] === 'submit')
        {
            $view->vars['clicked'] = $form->isClicked();
        }

        if ($options['prevent_double_click']) {
            $view->vars['attr']['data-prevent-double-click'] = "true";
        }

        if ($options['disabled'] ?? false) {
            $view->vars['attr']['class'] = trim(($view->vars['attr']['class'] ?? "") . ' govuk-button--disabled');
        }

        if ($options['is_start_button'] ?? false) {
            $view->vars['attr']['class'] = trim(($view->vars['attr']['class'] ?? "") . ' govuk-button--start');
        }

        $view->vars['is_start_button'] = $options['is_start_button'];
        $view->vars['label_html'] = $options['label_html'];
    }

    public function getParent(): string
    {
        return ExtendedButtonType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'gds_button';
    }
}
