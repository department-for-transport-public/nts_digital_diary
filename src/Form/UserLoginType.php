<?php

namespace App\Form;

use Ghost\GovUkFrontendBundle\Form\Type\ButtonType;
use Ghost\GovUkFrontendBundle\Form\Type\CheckboxType;
use Ghost\GovUkFrontendBundle\Form\Type\FieldsetType;
use Ghost\GovUkFrontendBundle\Form\Type\InputType;
use Ghost\GovUkFrontendBundle\Form\Type\PasswordType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserLoginType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('group', FieldsetType::class, [
                'label' => "login.group.label",
                'help' => "login.group.help",
                'label_attr' => ['class' => 'govuk-fieldset__legend--m'],
            ])
            ->add('remember_me', CheckboxType::class, [
                'label' => 'login.remember-me.label',
                'label_attr' => ['class' => 'govuk-label--s'],
                'help' => 'login.remember-me.help',
                'help_attr' => ['class' => 'govuk-input--width-30'],
                'small' => true,
            ])
            ->add('sign_in', ButtonType::class, [
                'type' => 'submit',
                'label' => "login.sign-in.label",
            ]);

        $builder->get('group')
            ->add('email', InputType::class, [
                'label' => "login.email.label",
                'help' => "login.email.help",
                'label_attr' => ['class' => 'govuk-label--s'],
                'attr' => ['class' => 'govuk-input--width-15'],
            ])
            ->add('password', PasswordType::class, [
                'label' => "login.password.label",
                'help' => "login.password.help",
                'label_attr' => ['class' => 'govuk-label--s'],
                'attr' => ['class' => 'govuk-input--width-15'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'csrf_token_id' => 'authenticate',
            'attr' => ['autocomplete' => 'off'],
        ]);
    }
}
