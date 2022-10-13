<?php

namespace App\ListPage;

use App\ListPage\Field\FilterableInterface;
use App\ListPage\Field\Simple;
use App\ListPage\Field\SortableInterface;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ListPageForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $hasFilterFields = false;
        $hasSortableFields = false;

        /**
         * @var $field Simple
         */
        foreach($options['fields'] as $field) {
            $fieldName = $field->getId();

            if ($field instanceof SortableInterface && $field->getSortable()) {
                $hasSortableFields = true;
            }

            if (!$field instanceof FilterableInterface) {
                continue;
            }

            $hasFilterFields = true;
            $builder->add($fieldName, $field->getFormClass(), array_merge([
                'label' => "{$field->getLabel()} (filter)",
                'label_attr' => ['class' => 'govuk-visually-hidden'],
                'attr' => [
                    'placeholder' => $field->getLabel(),
                    'class' => 'filter',
                ],
            ], $field->getFormOptions()));
        }

        if ($hasFilterFields) {
            $builder
                ->add('submit', Gds\ButtonType::class, [
                    'label' => 'Apply<span class="govuk-visually-hidden"> filters</span>',
                    'label_html' => true,
                ]);
        }
        if ($hasFilterFields || $hasSortableFields) {
            $builder
                ->add('clear', Gds\ButtonType::class, [
                    'label' => 'Clear<span class="govuk-visually-hidden"> filters</span>',
                    'label_html' => true,
                    'attr' => [
                        'class' => 'govuk-button--warning',
                    ]
                ])
                ->add('orderBy', HiddenType::class)
                ->add('orderDirection', HiddenType::class);
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'method' => 'get',
            'csrf_protection' => false,
            'allow_extra_fields' => true,
        ]);

        $resolver->setRequired('fields');
        $resolver->setAllowedTypes('fields', Simple::class.'[]');
    }

    public function getBlockPrefix(): string
    {
        return "";
    }
}