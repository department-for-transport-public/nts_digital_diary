<?php

namespace Ghost\GovUkFrontendBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType as ExtendedTextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InputType extends AbstractType
{
    public function getParent(): string
    {
        return ExtendedTextType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'gds_input';
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['prefix'] = $options['prefix'];
        $view->vars['prefix_html'] = $options['prefix_html'];
        $view->vars['suffix'] = $options['suffix'];
        $view->vars['suffix_html'] = $options['suffix_html'];
        $view->vars['type'] = $options['type'];
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'prefix' => false,
            'prefix_html' => false,
            'suffix' => false,
            'suffix_html' => false,
            'type' => null,
        ]);
    }
}
