<?php

namespace Ghost\GovUkFrontendBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType as ExtendedTextareaType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TextareaType extends AbstractType
{
    public function getParent(): string
    {
        return ExtendedTextareaType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'gds_textarea';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'max_length' => null,
            'max_words' => null,
            'threshold' => null,
            'rows' => 5,
        ]);

        $resolver->setAllowedTypes('rows', ['integer']);
        $resolver->setAllowedTypes('max_length', ['null', 'int']);
        $resolver->setAllowedTypes('max_words', ['null', 'int']);
        $resolver->setAllowedTypes('threshold', ['null', 'int']);

        $resolver->setNormalizer('max_words', function (Options $options, $value) {
            if (($options['max_length'] ?? null) && $value) {
                throw new InvalidOptionsException('The max_length and max_words options may not be set simultaneously');
            }

            return $value;
        });
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['attr']['rows'] = $options['rows'];

        $characterCountOptions = ['max_length', 'max_words', 'threshold'];

        $characterCountEnabled = array_reduce($characterCountOptions, function ($carry, $optName) use ($options) {
            return $carry || ($options[$optName] ?? null);
        });

        if ($characterCountEnabled) {
            $view->vars['character_count'] = 'enabled';
            foreach ($characterCountOptions as $optName) {
                $view->vars[$optName] = $options[$optName];
            }
        }
    }
}
