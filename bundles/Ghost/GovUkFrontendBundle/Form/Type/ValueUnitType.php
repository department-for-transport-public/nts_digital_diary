<?php

namespace Ghost\GovUkFrontendBundle\Form\Type;

use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Ghost\GovUkFrontendBundle\Model\ValueUnitInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ValueUnitType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $defaultValueOptions = [
            'label' => false,
            'attr' => [
            ],
        ];
        $defaultUnitsOptions = [
            'label' => false,
            'attr' => [
                'class' => 'govuk-radios--inline',
            ],
        ];

        $builder
            ->setDataMapper(new ValueUnitDataMapper($options['allow_empty']))
            ->add('value', $options['value_form_type'], array_merge($defaultValueOptions, $options['value_options']))
            ->add('unit', $options['unit_form_type'], array_merge($defaultUnitsOptions, $options['unit_options']))
        ;
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['label_is_page_heading'] = $options['label_is_page_heading'];
        $view->vars['fieldset_attr'] = ['id' => $view->vars['id']];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ValueUnitInterface::class,
            'value_form_type' => Gds\NumberType::class,
            'unit_form_type' => Gds\ChoiceType::class,
            'value_options' => [],
            'unit_options' => [],
            'label_is_page_heading' => false,
            'attr' => ['class' => 'govuk-fieldset--inline'],
            'allow_empty' => false,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'gds_value_units';
    }
}
