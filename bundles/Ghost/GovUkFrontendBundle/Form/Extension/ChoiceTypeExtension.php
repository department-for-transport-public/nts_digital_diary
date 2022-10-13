<?php

namespace Ghost\GovUkFrontendBundle\Form\Extension;

use Ghost\GovUkFrontendBundle\EventListener\ContextualOptionsFormListener;
use Ghost\GovUkFrontendBundle\Form\Type\ChoiceType;
use Ghost\GovUkFrontendBundle\Form\Type\EntityType;
use Ghost\GovUkFrontendBundle\Form\View\ChoiceGroupView;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\ChoiceList\ChoiceListInterface;
use Symfony\Component\Form\ChoiceList\View\ChoiceGroupView as SymfonyChoiceGroupView;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ChoiceTypeExtension extends AbstractTypeExtension
{
    public static function getExtendedTypes(): iterable
    {
        return [
            ChoiceType::class,
            EntityType::class,
        ];
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (is_array($options['choice_attr'])) {
            $options['choice_attr'] = function($value, $key) use ($options) {
                return $options['choice_attr'][$key] ?? [];
            };
        }

        $choiceOptions = $options['choice_options'];
        $builder->addEventSubscriber(new ContextualOptionsFormListener(
            function (FormEvent $event, $name, array $options) use ($choiceOptions) {
                /** @var ChoiceListInterface $choiceList */
                $choiceList = $event->getForm()->getConfig()->getAttribute('choice_list');
                if (is_callable($choiceOptions)) {
                    return array_merge($options, $choiceOptions($choiceList->getChoices()[$options['value']], $choiceList->getOriginalKeys()[$options['value']], $options['value']));
                }
                return array_merge($options, $choiceOptions[$choiceList->getOriginalKeys()[$options['value']]] ?? []);
            }
        ));
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['has_conditional'] = false;
        foreach ($form->all() as $v) {
            if ((($v->getConfig()->getOption("conditional_form_name")) ?? false) || (($v->getConfig()->getOption("conditional_hide_form_names")) ?? false)) {
                $view->vars['has_conditional'] = true;
                $view->vars['attr']['data-module'] = $view->vars['multiple'] ? 'govuk-checkboxes' : 'govuk-radios';
                break;
            }
        }

        if ($options['group_by']) {
            foreach($view->vars['choices'] as $idx => $choice) {
                if ($choice instanceof SymfonyChoiceGroupView) {
                    $view->vars['choices'][$idx] = new ChoiceGroupView($choice, $options['group_label_attr'], $options['group_label_heading_element']);
                }
            }
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'placeholder' => false,
            'choice_options' => null,
            'expanded' => true,
            'invalid_message' => 'common.choice.invalid',
            'choice_translation_domain' => null,
            'group_label_attr' => null,
            'group_label_heading_element' => 'h1',
        ]);

        $resolver->setAllowedTypes('choice_attr', ['null', 'array', 'callable']);
        $resolver->setAllowedTypes('choice_options', ['null', 'array', 'callable']);
        $resolver->setAllowedTypes('group_label_attr', ['null', 'array']);
        $resolver->setAllowedTypes('group_label_heading_element', ['string']);

        $resolver->setNormalizer('multiple', function (Options $options, $value) {
            if (true === $value && false === $options['expanded']) {
                throw new InvalidOptionsException('Option combination multiple=true, expanded=false is unsupported');
            }

            return $value;
        });
    }
}