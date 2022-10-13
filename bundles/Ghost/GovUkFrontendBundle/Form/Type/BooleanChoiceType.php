<?php

namespace Ghost\GovUkFrontendBundle\Form\Type;

use RuntimeException;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BooleanChoiceType extends AbstractType
{
    public function getParent(): string
    {
        return ChoiceType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'gds_boolean_choice';
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer(new CallbackTransformer(
            function ($property) {
                switch (true) {
                    case $property === null: return null;
                    case $property === false: return "false";
                    case $property === true: return "true";
                    default : throw new RuntimeException("unexpected property value: $property");
                }
            },
            function ($property) {
                switch (true) {
                    case $property === null : return null;
                    case $property === "false" : return false;
                    case $property === "true" : return true;
                    default : throw new RuntimeException("unexpected property value: $property");
                }
            }
        ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'expanded' => true,
            'choices' => [
                'boolean.true' => "true",
                'boolean.false' => "false",
            ],
            'choice_translation_domain' => 'messages',
            'attr' => ['class' => 'govuk-radios--inline'],
        ]);
    }
}
