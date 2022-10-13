<?php

namespace App\Form\OnBoarding;

use Ghost\GovUkFrontendBundle\Form\Type\ButtonType;
use Ghost\GovUkFrontendBundle\Form\Type\FieldsetType;
use Ghost\GovUkFrontendBundle\Form\Type\InputType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OtpLoginType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $prefix = "login";
        $builder
            ->add('group', FieldsetType::class, [
                'label' => "{$prefix}.group.label",
                'help' => "{$prefix}.group.help",
                'label_attr' => ['class' => 'govuk-fieldset__legend--s'],
            ])
            ->add('sign_in', ButtonType::class, [
                'type' => 'submit',
                'label' => "{$prefix}.sign-in.label",
            ])
        ;
        $builder->get('group')
            ->add('identifier', InputType::class, [
                'label' => "{$prefix}.identifier.label",
                'help' => "{$prefix}.identifier.help",
                'attr' => ['class' => 'govuk-input--width-10'],
                'empty_data' => '',
            ])
            ->add('passcode', InputType::class, [
                'label' => "{$prefix}.password.label",
                'help' => "{$prefix}.password.help",
                'attr' => ['class' => 'govuk-input--width-10'],
                'empty_data' => '',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'translation_domain' => 'on-boarding',
        ]);
    }
}