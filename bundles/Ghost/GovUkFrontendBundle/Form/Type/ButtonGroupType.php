<?php


namespace Ghost\GovUkFrontendBundle\Form\Type;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ButtonGroupType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'mapped' => false,
            'label' => false,
            'attr' => ['class' => 'govuk-button-group'],
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'gds_button_group';
    }
}