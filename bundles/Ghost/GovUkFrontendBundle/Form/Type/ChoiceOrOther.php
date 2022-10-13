<?php

namespace Ghost\GovUkFrontendBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ChoiceOrOther extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('choice', ChoiceType::class, array_merge(
                $options['choice_options'], ['property_path' => $options['choice_property_path']]
            ))
            ->add('other', InputType::class, array_merge(
                $options['other_options'], ['property_path' => $options['other_property_path']]
            ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'inherit_data' => true,
            'choice_options' => [],
            'other_options' => [],
        ]);

        $resolver->setRequired([
            'choice_property_path',
            'other_property_path',
        ]);
    }
}
