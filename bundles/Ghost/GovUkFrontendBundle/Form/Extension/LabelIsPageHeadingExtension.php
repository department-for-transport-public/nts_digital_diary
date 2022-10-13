<?php

namespace Ghost\GovUkFrontendBundle\Form\Extension;

use Ghost\GovUkFrontendBundle\Form\Type\ChoiceType;
use Ghost\GovUkFrontendBundle\Form\Type\EntityType;
use Ghost\GovUkFrontendBundle\Form\Type\FieldsetType;
use Ghost\GovUkFrontendBundle\Form\Type\TextareaType;
use Ghost\GovUkFrontendBundle\Form\Type\InputType;
use Ghost\GovUkFrontendBundle\Form\Type\TimeType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LabelIsPageHeadingExtension extends AbstractTypeExtension
{
    public static function getExtendedTypes(): iterable
    {
        return [
            TextareaType::class,
            InputType::class,
            TimeType::class,
            FieldsetType::class,
            ChoiceType::class,
            EntityType::class,
        ];
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'label_is_page_heading' => false,
        ]);
        $resolver->setAllowedTypes('label_is_page_heading', ['bool']);
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['label_is_page_heading'] = $options['label_is_page_heading'];
        if ($options['label_is_page_heading']) {
            array_splice($view->vars['block_prefixes'], -1, 0, ['label_is_page_heading']);
        }
    }
}