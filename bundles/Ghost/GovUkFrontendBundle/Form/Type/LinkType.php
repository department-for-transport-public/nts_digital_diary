<?php


namespace Ghost\GovUkFrontendBundle\Form\Type;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LinkType extends AbstractType
{
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['attr'] = $view->vars['attr'] + ['href' => $options['href']];
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'mapped' => false,
            'attr' => ['class' => 'govuk-link'],
        ]);
        $resolver->setRequired(['href']);
    }

    public function getBlockPrefix(): string
    {
        return 'gds_link';
    }
}