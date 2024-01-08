<?php

namespace Ghost\GovUkFrontendBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType as ExtendedChoiceType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ChoiceType extends AbstractType
{
    public function getParent(): string
    {
        return ExtendedChoiceType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'gds_choice';
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        parent::buildView($view, $form, $options);

        $view->vars['fieldset_attr'] = $options['fieldset_attr'];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefault('fieldset_attr', []);
        $resolver->setAllowedTypes('fieldset_attr', 'array');
    }
}
