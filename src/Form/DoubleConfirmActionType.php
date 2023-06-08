<?php

namespace App\Form;

use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class DoubleConfirmActionType extends AbstractType
{
    public function getParent()
    {
        return ConfirmActionType::class;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('confirmation', Gds\CheckboxType::class, array_merge([
                'priority' => 1,
                'constraints' => [new NotBlank(message: "common.option.required")],
            ], $options['confirmation_checkbox_options']));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired([
            'confirmation_checkbox_options',
        ]);
    }
}